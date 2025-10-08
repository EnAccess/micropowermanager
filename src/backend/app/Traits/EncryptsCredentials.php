<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

trait EncryptsCredentials {
    /**
     * Encrypt a credential field value.
     *
     * @param string|null $value
     *
     * @return string|null
     */
    protected function encryptCredentialField(?string $value): ?string {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::encryptString($value);
        } catch (\Exception $e) {
            return $value; // Return original value if encryption fails
        }
    }

    /**
     * Decrypt a credential field value.
     *
     * @param string|null $value
     *
     * @return string|null
     */
    protected function decryptCredentialField(?string $value): ?string {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // Return original value if decryption fails
        }
    }

    /**
     * Encrypt multiple credential fields in an array.
     *
     * @param array $data
     * @param array $fieldsToEncrypt
     *
     * @return array
     */
    protected function encryptCredentialFields(array $data, array $fieldsToEncrypt): array {
        $encryptedData = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $fieldsToEncrypt)) {
                $encryptedData[$key] = $this->encryptCredentialField($value);
            } else {
                $encryptedData[$key] = $value;
            }
        }

        return $encryptedData;
    }

    /**
     * Decrypt multiple credential fields on a model.
     *
     * @param object $model
     * @param array  $fieldsToDecrypt
     *
     * @return object
     */
    protected function decryptCredentialFields(object $model, array $fieldsToDecrypt): object {
        if (!$model) {
            return $model;
        }

        foreach ($fieldsToDecrypt as $field) {
            if (isset($model->$field) && $model->$field !== null) {
                $model->$field = $this->decryptCredentialField($model->$field);
            }
        }

        return $model;
    }
}
