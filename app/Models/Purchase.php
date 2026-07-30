<?php

namespace App\Models;

use App\Foundation\Model;
use PDO;

class Purchase extends Model
{
    /**
     * @param array{
     *   amount:int|string,
     *   buyer:string,
     *   receipt_id:string,
     *   items:string,
     *   buyer_email:string,
     *   buyer_ip:string,
     *   note:string,
     *   city:string,
     *   phone:string,
     *   hash_key:string,
     *   entry_at:string,
     *   entry_by:int|string
     * } $data
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO purchases
            (amount, buyer, receipt_id, items, buyer_email, buyer_ip, note, city, phone, hash_key, entry_at, entry_by)
            VALUES
            (:amount, :buyer, :receipt_id, :items, :buyer_email, :buyer_ip, :note, :city, :phone, :hash_key, :entry_at, :entry_by)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':amount' => (int) $data['amount'],
            ':buyer' => $data['buyer'],
            ':receipt_id' => $data['receipt_id'],
            ':items' => $data['items'],
            ':buyer_email' => $data['buyer_email'],
            ':buyer_ip' => $data['buyer_ip'],
            ':note' => $data['note'],
            ':city' => $data['city'],
            ':phone' => $data['phone'],
            ':hash_key' => $data['hash_key'],
            ':entry_at' => $data['entry_at'],
            ':entry_by' => (int) $data['entry_by'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** @param array{date_from?:string, date_to?:string, entry_by?:string} $filters */
    public function countFiltered(array $filters = []): int
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM purchases WHERE 1=1' . $where);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array{date_from?:string, date_to?:string, entry_by?:string} $filters
     * @return list<array<string, mixed>>
     */
    public function findFiltered(array $filters = [], ?int $limit = null, int $offset = 0): array
    {
        [$where, $params] = $this->buildFilterClause($filters);

        $sql = 'SELECT id, amount, buyer, receipt_id, items, buyer_email, buyer_ip,
                       note, city, phone, hash_key, entry_at, entry_by
                FROM purchases WHERE 1=1' . $where . '
                ORDER BY entry_at DESC, id DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array{date_from?:string, date_to?:string, entry_by?:string} $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildFilterClause(array $filters): array
    {
        $where = '';
        $params = [];

        if (!empty($filters['date_from'])) {
            $where .= ' AND entry_at >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND entry_at <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        if (isset($filters['entry_by']) && $filters['entry_by'] !== '') {
            $where .= ' AND entry_by = :entry_by';
            $params[':entry_by'] = (int) $filters['entry_by'];
        }

        return [$where, $params];
    }
}
