<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\Auth\LoginRequest;
    use App\Http\Requests\Auth\RegisterRequest;
    use App\Http\Traits\ApiResponse;
    use App\Models\User;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Validation\ValidationException;

    class AuthController extends Controller
    {
        use ApiResponse;

        /**
         * Register
         *
         * Creates the account and returns a Bearer token for it.
         */
        public function register(RegisterRequest $request): JsonResponse
        {
            $user = User::create($request->validated());
            $token = $user->createToken('auth_token')->plainTextToken;

            /**
             * Account created.
             *
             * @status 201
             * @body array{data: array{user: User, token: string}, message: string}
             */
            return $this->success([
                'user' => $user,
                'token' => $token,
            ], 'User registered successfully', 201);
        }

        /**
         * Log in
         *
         * Returns a Bearer token. Wrong credentials return 422.
         */
        public function login(LoginRequest $request): JsonResponse
        {
            $credentials = $request->validated();

            $user = User::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            /**
             * Logged in.
             *
             * @body array{data: array{user: User, token: string}, message: string}
             */
            return $this->success([
                'user' => $user,
                'token' => $token,
            ], 'Login successful');
        }

        /**
         * Log out
         *
         * Revokes only the token used for this request.
         */
        public function logout(Request $request): JsonResponse
        {
            $request->user()->currentAccessToken()->delete();

            /**
             * Logged out.
             *
             * @body array{data: array{}, message: string}
             */
            return $this->success(
                message: 'Logged out successfully'
            );
        }

        /**
         * Current user
         */
        public function me(Request $request): JsonResponse
        {
            /**
             * The authenticated user.
             *
             * @body array{data: User, message: string}
             */
            return $this->success(
                data: $request->user()
            );
        }
    }
