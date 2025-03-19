<?php

require_once __DIR__ . '/Database.php';

class Word
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * @param string $text
     * @return void
     * @throws Exception
     */
    public function storeWords(string $text): void
    {
        $words = $this->extractWords($text);

        if($words) {
            $wordsCounts = [];
            foreach ($words as $word) {
                if (!isset($wordsCounts[$word])) {
                    $wordsCounts[$word] = 0;
                }
                $wordsCounts[$word]++;
            }

            $this->upsertWords($wordsCounts);
        }else{
            throw new Exception("No valid words to store.");
        }
    }

    /**
     * @param string $text
     * @return array
     */
    private function extractWords(string $text): array {
        // Convert text to lowercase
        $text = strtolower($text);

        // Remove punctuation with a regex that replaces non-alphabetic or non-digit chars with space
        $cleaned = preg_replace('/[^a-z0-9]+/i', ' ', $text);

        // Split on one or more whitespace
        return preg_split('/\s+/', trim($cleaned), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @param $wordsCounts
     * @return void
     */
    private function upsertWords($wordsCounts): void
    {
        $this->pdo->beginTransaction();

        // SQLite version is 3.24+
        $sql = 'INSERT INTO word_counts(word, count)
         VALUES (:word, :count)
         ON CONFLICT(word) DO UPDATE SET count = count + :count';
        $insertStmt = $this->pdo->prepare($sql);

        foreach ($wordsCounts as $word => $count) {
            $insertStmt->execute([
                ':word' => $word,
                ':count' => $count
            ]);
        }

        // Commit everything with single transaction for faster execution time
        $this->pdo->commit();
    }

    /**
     * Returns an associative array of all words => count from the database.
     *
     * @return array
     */
    public function getAllCounts(): array
    {
        $result = $this->pdo->query( 'SELECT word, count FROM word_counts');

        return  $result->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Returns how many times the given word has appeared, or 0 if not found.
     *
     * @param string $word
     * @return int
     */
    public function getWordCount(string $word): int
    {
        $stmt = $this->pdo->prepare('SELECT count FROM word_counts WHERE word = :word');
        $stmt->execute([':word' => strtolower($word)]);

        return (int)($stmt->fetchColumn() ?? 0);
    }

}
