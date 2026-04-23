<?php

namespace Tests\Unit;

use App\Http\Requests\User\UpdateProfileRequest;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

class UpdateProfileRequestTest extends TestCase
{
    public function test_profile_update_accepts_numeric_nif_payload(): void
    {
        $request = UpdateProfileRequest::create('/api/v1/user', 'PUT', [
            'nif' => 315454810,
        ]);

        $prepare = new ReflectionMethod($request, 'prepareForValidation');
        $prepare->setAccessible(true);
        $prepare->invoke($request);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails());
        $this->assertSame('315454810', $request->input('nif'));
    }
}
