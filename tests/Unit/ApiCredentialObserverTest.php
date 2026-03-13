<?php

use App\Contracts\ApiCredentialProvider;
use App\Models\Concerns\HasEncryptedCredentials;
use Illuminate\Database\Eloquent\Model;

test('default credential attributes returns api_token', function () {
    $model = new class extends Model implements ApiCredentialProvider
    {
        use HasEncryptedCredentials;
    };

    expect($model->getCredentialAttributes())->toBe(['api_token']);
});

test('custom credential attributes can be overridden', function () {
    $model = new class extends Model implements ApiCredentialProvider
    {
        use HasEncryptedCredentials;

        public function getCredentialAttributes(): array
        {
            return ['access_token', 'refresh_token'];
        }
    };

    expect($model->getCredentialAttributes())->toBe(['access_token', 'refresh_token']);
});
