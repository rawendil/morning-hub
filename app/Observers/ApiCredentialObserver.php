<?php

namespace App\Observers;

use App\Contracts\ApiCredentialProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ApiCredentialObserver
{
    public function created(Model&ApiCredentialProvider $model): void
    {
        Log::channel('security')->info($model->getProviderName().' connection created', [
            'user_id' => $model->getAttribute('user_id'),
            'connection_id' => $model->getKey(),
            'connection_name' => $model->getAttribute('name'),
        ]);
    }

    public function updated(Model&ApiCredentialProvider $model): void
    {
        $credentialAttributes = $model->getCredentialAttributes();
        $changedCredentials = array_filter(
            $credentialAttributes,
            fn (string $attr) => $model->wasChanged($attr),
        );

        $firstCredential = $credentialAttributes[0] ?? 'api_token';
        $tokenChanged = $model->wasChanged($firstCredential);

        Log::channel('security')->info($model->getProviderName().' connection updated', [
            'user_id' => $model->getAttribute('user_id'),
            'connection_id' => $model->getKey(),
            'connection_name' => $model->getAttribute('name'),
            'token_changed' => $tokenChanged,
            'masked_token' => $tokenChanged ? $model->maskedToken($firstCredential) : null,
            'changed_credentials' => array_values($changedCredentials),
        ]);
    }

    public function deleted(Model&ApiCredentialProvider $model): void
    {
        Log::channel('security')->info($model->getProviderName().' connection deleted', [
            'user_id' => $model->getAttribute('user_id'),
            'connection_id' => $model->getKey(),
            'connection_name' => $model->getAttribute('name'),
        ]);
    }
}
