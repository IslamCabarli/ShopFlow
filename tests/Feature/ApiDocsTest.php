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
