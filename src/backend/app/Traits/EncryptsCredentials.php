<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

trait EncryptsCredentials {
    /**
     * Encrypt a credential field value.
     */
    protected function encryptCredentialField(?string $value): ?string {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::encryptString($value);
        } catch (\Exception) {
            return $value; // Return original value if encryption fails
        }
    }

    /**
     * Decrypt a credential field value.
     */
    protected function decryptCredentialField(?string $value): ?string {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value; // Return original value if decryption fails
        }
    }

    /**
     * Encrypt multiple credential fields in an array.
     *
     * @param array<string, mixed> $data
     * @param string[]             $fieldsToEncrypt
     *
     * @return array<string, string>
     */
    protected function encryptCredentialFields(array $data, array $fieldsToEncrypt): array {
        $encryptedData = [];

        foreach ($data as $key => $value) {
            $encryptedData[$key] = in_array($key, $fieldsToEncrypt) ? $this->encryptCredentialField($value) : $value;
        }

        return $encryptedData;
    }

    /**
     * Decrypt multiple credential fields on a model.
     *
     * @param string[] $fieldsToDecrypt
     */
    protected function decryptCredentialFields(?object $model, array $fieldsToDecrypt): ?object {
        if (!$model) {
            return $model;
        }

        foreach ($fieldsToDecrypt as $field) {
            if (isset($model->$field)) {
                $model->$field = $this->decryptCredentialField($model->$field);
            }
        }

        return $model;
    }
}
