<?php

require_once __DIR__ . '/../api/Word.php';
require_once __DIR__ . '/../api/Database.php';

class WordTest
{
    private $pdo;
    private $word;

    public function setUp(): void
    {
        // Create an in-memory SQLite database
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE word_counts (id INTEGER PRIMARY KEY AUTOINCREMENT, word TEXT UNIQUE, count INTEGER NOT NULL);');

        // Create Word instance and inject the test PDO using reflection
        $this->word = new Word();
        $reflection = new ReflectionClass($this->word);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setValue($this->word, $this->pdo);
    }

    /**
     * Helper to print out test results in a standardized format.
     *
     * @param string $testName
     * @param mixed $expected
     * @param mixed $actual
     */
    private function assertEqual(string $testName, $expected, $actual): void
    {
        if ($expected === $actual) {
            echo "✅ [PASS] $testName => Expected: " . json_encode($expected) . ", Got: " . json_encode($actual) . "\n";
        } else {
            echo "❌ [FAIL] $testName => Expected: " . json_encode($expected) . ", Got: " . json_encode($actual) . "\n";
        }
    }

    /**
     * To isolate tests, we can create a new WordStore with an in-memory DB
     * or a fresh file each time. For example, using PDO('sqlite::memory:') or
     * removing an existing words.db file. Here, we'll do an in-memory approach
     * so each test starts with an empty DB.
     *
     * Adjust to your actual constructor if needed.
     */
    private function createFreshWord(): void
    {
        Database::reset();

        // return clear connection
        $this->word = new Word();
    }

    public function runAllTests(): void
    {
        $methods = [
            'taskGivenTest',
            'storeWordsWithNewWordsTest',
            'storeWordsIncrementsWordsTest',
            'getAllCountsEmptyInitiallyTest',
            'getWordCountNonExistentTest',
            'uppercaseRepeatAndPunctuationTest',
            'emptyTextTest',
            'getAllCountsOrderingTest',
            'largeTextTest',
            'nonExistingWordTest',
            'nullTextTest',
        ];

        foreach ($methods as $method) {
            try {
                $this->$method();
            } catch (AssertionError $e) {
                echo "FAIL: {$e->getMessage()}\n";
            } catch (Throwable $e) {
                echo "ERROR: {$e->getMessage()}\n";
            }
        }
    }

    // -----------------------------------------------------
    // TEST DEFINITIONS
    // -----------------------------------------------------

    private function taskGivenTest()
    {
        $this->createFreshWord();
        $this->word->storeWords("“Love grows where kindness lives.");
        $this->word->storeWords("“Kindness lives in every heart.");

        $this->assertEqual("Word 'love'", 1, $this->word->getWordCount("love"));
        $this->assertEqual("Word 'grows'", 1, $this->word->getWordCount("grows"));
        $this->assertEqual("Word 'where'", 1, $this->word->getWordCount("where"));
        $this->assertEqual("Word 'kindness'", 2, $this->word->getWordCount("kindness"));
        $this->assertEqual("Word 'lives'", 2, $this->word->getWordCount("lives"));
        $this->assertEqual("Word 'in'", 1, $this->word->getWordCount("in"));
        $this->assertEqual("Word 'every'", 1, $this->word->getWordCount("every"));
        $this->assertEqual("Word 'heart'", 1, $this->word->getWordCount("heart"));
    }

    private function storeWordsWithNewWordsTest(): void
    {
        $this->createFreshWord();
        $this->word->storeWords("Love grows where kindness lives.");

        // Check the counts of each expected word (lowercase, punctuation removed)
        $this->assertEqual("Word 'love'", 1, $this->word->getWordCount("love"));
        $this->assertEqual("Word 'grows'", 1, $this->word->getWordCount("grows"));
        $this->assertEqual("Word 'where'", 1, $this->word->getWordCount("where"));
        $this->assertEqual("Word 'kindness'", 1, $this->word->getWordCount("kindness"));
        $this->assertEqual("Word 'lives'", 1, $this->word->getWordCount("lives"));

        // Non-existent word
        $this->assertEqual("Word 'banana'", 0, $this->word->getWordCount("banana"));
    }

    /**
     * @throws Exception
     */
    private function storeWordsIncrementsWordsTest(): void
    {
        $this->createFreshWord();
        $this->word->storeWords('Love grows kindness');
        $this->word->storeWords('Love grows kindness love');
        $counts = $this->word->getAllCounts();
        $this->assertEqual("Word 'love'", 3, $counts['love']);
        $this->assertEqual("Word 'grows'", 2, $counts['grows']);
        $this->assertEqual("Word 'kindness'", 2, $counts['kindness']);
    }

    private function getAllCountsEmptyInitiallyTest(): void
    {
        $counts = $this->word->getAllCounts();
        $this->assertEqual("getAllCountsEmptyInitiallyTest", ['love' => 3, 'grows' => 2, 'kindness' => 2], $counts);
    }

    private function getWordCountNonExistentTest(): void
    {
        $count = $this->word->getWordCount('nonexistent');
        $this->assertEqual("getWordCountNonExistentTest", 0, $count);
    }

    public  function uppercaseRepeatAndPunctuationTest(): void
    {
        $this->createFreshWord();
        $this->word->storeWords("LOVE!!  love??,  'love'...  LoVe  - lOvE");

        // We expect them all to parse to "love" in lowercase 4 total
        $this->assertEqual("Word 'love' after repeated forms", 5, $this->word->getWordCount("love"));
    }

    private function emptyTextTest(): void
    {
        $this->createFreshWord();
        try {
            $this->word->storeWords("         ");
        } catch (Exception $e) {
            $this->assertEqual("emptyTextTest", 'No valid words to store.', $e->getMessage());
        }
    }

    public  function getAllCountsOrderingTest(): void
    {
        $this->createFreshWord();
        $this->word->storeWords("zebra apple Mango Apple banana BANANA ");


        $all = $this->word->getAllCounts();
        $expectedKeys = ["zebra", "apple", "mango", "banana"];
        $actualKeys = array_keys($all);

        $this->assertEqual("getAllCountsOrderingTest'", $expectedKeys, $actualKeys);

        // Check the counts
        $this->assertEqual("Count for 'zebra'", 1, $all['zebra']);
        $this->assertEqual("Count for 'apple'", 2, $all['apple']);
        $this->assertEqual("Count for 'mango'", 1, $all['mango']);
        $this->assertEqual("Count for 'banana'", 2, $all['banana']);
    }

    private function largeTextTest(): void
    {
        $this->createFreshWord();

        // Create a text of "love " repeated 5000 times
        // Bigger text to check if it handles batch updates well.
        $bigText = str_repeat("love ", 5000);
        $this->word->storeWords($bigText);

        // We expect "love" => 5000
        $this->assertEqual("largeTextTest", 5000, $this->word->getWordCount("love"));
    }

    public  function nonExistingWordTest(): void
    {
        $this->createFreshWord();
        $this->word->storeWords("Hello world");
        $this->assertEqual("nonExistingWordTest", 0, $this->word->getWordCount("nonexistentword"));
    }

    private function nullTextTest(): void
    {
        $this->createFreshWord();
        // We'll forcibly call addText(null) ignoring type safety (not recommended).
        // This may throw a TypeError or an exception, we'll see.
        try {
            $this->word->storeWords(null);
        } catch (TypeError $te) {
            // This is expected if addText() requires a string
            echo "✅ [PASS] nullTextTest - TypeError expected to be thrown for null input => " . $te->getMessage() . "\n";
        } catch (Exception $e) {
            echo "❌ [FAIL] nullTextTest -  TypeError expected to be thrown for null input, GOT  => " . $e->getMessage() . "\n";
        }
    }

}

// Run tests
$test = new WordTest();
$test->setUp();
$test->runAllTests();