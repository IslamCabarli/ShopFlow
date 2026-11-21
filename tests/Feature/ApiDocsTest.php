<?php

    namespace Tests\Feature;

    use App\Models\User;
    use Illuminate\Support\Facades\Gate;
    use Tests\TestCase;

    class ApiDocsTest extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();

            // Docs are local-only by default; open them for the testing environment.
            Gate::define('viewApiDocs', fn (?User $user = null) => true);
        }

        public function test_openapi_document_covers_the_main_endpoints(): void
        {
            $document = $this->getJson('/docs/api.json')
                ->assertOk()
                ->json();

            $this->assertStringStartsWith('3.1', $document['openapi']);

            foreach ([
                         '/v1/auth/login',
                         '/v1/products',
                         '/v1/checkout',
                         '/v1/orders',
                         '/v1/admin/coupons',
                     ] as $suffix) {
                $this->assertNotNull(
                    $this->findPath($document, $suffix),
                    "Endpoint is missing from the generated docs: {$suffix}"
                );
            }
        }

        public function test_public_endpoints_need_no_auth_and_protected_ones_do(): void
        {
            $document = $this->getJson('/docs/api.json')->json();

            $login = $this->findPath($document, '/v1/auth/login')['post'];
            $checkout = $this->findPath($document, '/v1/checkout')['post'];

            $this->assertSame([], $this->effectiveSecurity($document, $login));
            $this->assertNotEmpty($this->effectiveSecurity($document, $checkout));
        }

        public function test_responses_use_the_envelope_and_the_real_status_codes(): void
        {
            $document = $this->getJson('/docs/api.json')->json();

            $checkout = $this->findPath($document, '/v1/checkout')['post']['responses'];
            $this->assertArrayHasKey('201', $checkout);
            $this->assertArrayNotHasKey('200', $checkout);
            $this->assertArrayHasKey('409', $checkout);

            $product = $this->findPath($document, '/v1/products/{product}')['get']['responses']['200'];
            $this->assertStringContainsString('ProductResource', json_encode($product));
        }

        public function test_every_operation_has_a_summary_and_a_described_success_schema(): void
        {
            $document = $this->getJson('/docs/api.json')->json();

            $problems = [];

            foreach ($document['paths'] as $path => $methods) {
                foreach ($methods as $method => $operation) {
                    $name = strtoupper($method) . ' ' . $path;

                    if (empty($operation['summary'])) {
                        $problems[] = "{$name}: missing summary";
                    }

                    foreach ($operation['responses'] as $status => $response) {
                        if (!str_starts_with((string) $status, '2')) {
                            continue;
                        }

                        $schema = $response['content']['application/json']['schema'] ?? [];

                        if (!isset($schema['properties']) && !isset($schema['$ref']) && !isset($schema['allOf'])) {
                            $problems[] = "{$name}: {$status} response has no schema (add @body)";
                        }
                    }
                }
            }

            $this->assertSame([], $problems, implode("\n", $problems));
        }

        private function findPath(array $document, string $suffix): ?array
        {
            foreach ($document['paths'] as $path => $item) {
                if (str_ends_with($path, $suffix)) {
                    return $item;
                }
            }

            return null;
        }

        private function effectiveSecurity(array $document, array $operation): array
        {
            return array_key_exists('security', $operation)
                ? $operation['security']
                : ($document['security'] ?? []);
        }
    }
