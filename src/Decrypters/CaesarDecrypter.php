<?php

declare(strict_types=1);

namespace App\Decrypters;

class CaesarDecrypter
{
    /**
     * @return mixed|string
     */
    public function cipher($ch, int $key)
    {
        if (!ctype_alpha($ch)) {
            return $ch;
        }

        $offset = ord(ctype_upper($ch) ? 'A' : 'a');
        return chr((ord($ch) + $key - $offset) % 26 + $offset);
    }

    public function encipher(string $input, int $key): string
    {
        $output = "";
        $inputArr = str_split($input);
        foreach ($inputArr as $ch) {
            $output .= $this->cipher($ch, $key);
        }

        return $output;
    }

    public function decipher(string $input, int $key): string
    {
        return $this->encipher($input, 26 - $key);
    }

    public function decryptProgrammeArray(array $encryptedArray): array
    {
        $decrypted = [];
        $decrypted['name'] = $this->decipher($encryptedArray['name'], 8);
        $decrypted['description'] = $this->decipher($encryptedArray['description'], 8);
        $decrypted['startDate'] = $encryptedArray['startDate'];
        $decrypted['endDate'] = $encryptedArray['endDate'];
        $decrypted['isOnline'] = $encryptedArray['isOnline'] ?: false;
        $decrypted['maxParticipants'] = $encryptedArray['maxParticipants'];

        return $decrypted;
    }
}
