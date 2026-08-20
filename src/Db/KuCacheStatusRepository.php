<?php

namespace App\Db;

class KuCacheStatusRepository
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function markSynced(string $kuCode, string $kuName, int $parcelCount): void
    {
        $sql = 'INSERT INTO ku_cache_status (ku_code, ku_name, last_synced_at, parcel_count)
                VALUES (:ku_code, :ku_name, :last_synced_at, :parcel_count)
                ON DUPLICATE KEY UPDATE
                    ku_name = VALUES(ku_name),
                    last_synced_at = VALUES(last_synced_at),
                    parcel_count = VALUES(parcel_count)';

        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'ku_code' => $kuCode,
            'ku_name' => $kuName,
            'last_synced_at' => date('Y-m-d H:i:s'),
            'parcel_count' => $parcelCount,
        ]);
    }
}
