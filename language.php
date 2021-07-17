<?php

libxml_use_internal_errors(true);

$sentences = [];
$document = new DOMDocument();
$document->loadHTML(file_get_contents('https://en.wikipedia.org/wiki/Pug'));

/**
 * Class Dictionary
 */
class Dictionary
{
    /**
     * A collection of sentences.
     *
     * @var array
     */
    protected $sentences = [];


    /**
     * Parses an individual paragraph.
     *
     * @param string $paragraph
     */
    public function addParagraph($paragraph = '')
    {
        foreach ($this->getComponents($paragraph) as $sentence) {
            $this->addSentence(Sentence::create($sentence));
        }
    }


    /**
     * Registers a sentence within the dictionary.
     *
     * @param Sentence $sentence
     * @return $this
     */
    public function addSentence(Sentence $sentence)
    {
        if (!$sentence->getContent()) {
            return $this;
        }

        $this->sentences[] = $sentence;

        return $this;
    }


    /**
     * Returns all internal sentences.
     *
     * @return array
     */
    public function getSentences()
    {
        return $this->sentences;
    }


    /**
     * Returns a categorized grouping of each noun along with each occurrence count.
     *
     * @return array
     */
    public function getNounOccurrences()
    {
        $groupings = [];

        foreach ($this->getNouns() as $noun) {
            if (!isset($groupings[(string)$noun])) {
                $groupings[(string)$noun] = 0;
            }

            $groupings[(string)$noun]++;
        }

        return $groupings;
    }


    /**
     * Returns all nouns within the collected sentences.
     *
     * @return array
     */
    public function getNouns()
    {
        $nouns = [];

        foreach ($this->getSentences() as $sentence) {
            if (false) $sentence = new Sentence();

            foreach ($sentence->getSegments() as $grammar) {
                if ($grammar instanceof Noun) {
                    $nouns[] = $grammar;
                }
            }
        }

        return $nouns;
    }


    /**
     * Returns all of the components.
     *
     * @param string $string
     * @return array
     */
    public function getComponents($string = '')
    {
        return explode('.',
            str_replace('”', '',
                str_replace('“', '',
                    str_replace('"', '',
                        // Remove brackets and content between.
                        preg_replace('/\[([^\]]*)\]/', '',
                            // Remove parenthesis and content between.
                            preg_replace("/\(([^()]*+|(?R))*\)/", "", $string)
                        )
                    )
                )
            )
        );
    }
}


/**
 * Class Grammar
 */
class Grammar
{
    /**
     * A collection of phrase words.
     *
     * @var array
     */
    protected $words = [];


    /**
     * Shorthand creator.
     *
     * @return mixed
     */
    public static function create()
    {
        $class = get_called_class();

        return new $class();
    }


    /**
     * Registers a new word.
     *
     * @param string $word
     * @return $this
     */
    public function addWord($word = '')
    {
        if (!trim($word)) {
            return $this;
        }

        $this->words[] = $word;

        return $this;
    }


    /**
     * Removes the last assigned word.
     *
     * @return $this
     */
    public function popWord()
    {
        unset($this->words[count($this->words) - 1]);

        return $this;
    }


    /**
     * Returns all words.
     *
     * @return array
     */
    public function getWords()
    {
        return $this->words;
    }


    /**
     * Cast the component as a string.
     *
     * @return string
     */
    public function __toString()
    {
        return implode(' ', array_filter($this->getWords()));
    }
}


/**
 * Class Noun
 */
class Noun extends Grammar
{

}


/**
 * Class Adverb
 */
class Adverb extends Grammar
{

}


/**
 * Class Adjective
 */
class Adjective extends Grammar
{

}


/**
 * Class Verb
 */
class Verb extends Grammar
{

}


/**
 * Class Sentence
 */
class Sentence
{
    /**
     * A collection of words which build the sentence.
     *
     * @var array
     */
    protected $words = [];


    /**
     * The unmodified sentence text.
     *
     * @var string
     */
    protected $content = '';


    /**
     * The current grammatical segment.
     *
     * @var null
     */
    protected $segment = null;


    /**
     * The iterator value.
     *
     * @var int
     */
    protected $iterator = -1;


    /**
     * The parsed grammar segments.
     *
     * @var array
     */
    protected $segments = [];


    /**
     * Sentence constructor.
     *
     * @param string $content
     */
    public function __construct($content = '')
    {
        $content = trim($content);
        $this->words = explode(' ', trim(strtolower($content)));
        $this->content = $content;
        $this->segment = null;
        $this->iterator = -1;

        $this->parse();
    }


    /**
     * Shorthand helper for creating new sentences.
     *
     * @param string $content
     * @return mixed
     */
    public static function create($content = '')
    {
        $class = get_called_class();

        return new $class($content);
    }


    /**
     * Returns the unmodified content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }


    /**
     * Registers a new sentence component.
     *
     * @param $segment
     * @return $this
     */
    public function addSegment($segment)
    {
        if (!$segment instanceof Grammar) {
            return $this;
        }

        if (!$segment->getWords()) {
            return $this;
        }

        $this->segments[] = clone $segment;

        return $this;
    }


    /**
     * Returns all components.
     *
     * @return array
     */
    public function getSegments()
    {
        return $this->segments;
    }


    /**
     * Returns all words.
     *
     * @return array
     */
    public function getWords()
    {
        return $this->words;
    }


    /**
     * Returns the current iterator value.
     *
     * @return int
     */
    public function getIterator()
    {
        return $this->iterator;
    }


    /**
     * Increments the iterator.
     *
     * @return $this
     */
    public function addIterator()
    {
        $this->iterator++;

        return $this;
    }


    /**
     * Subtracts the iterator.
     *
     * @return $this
     */
    public function subtractIterator()
    {
        $this->iterator--;

        return $this;
    }


    /**
     * Returns the full word count.
     *
     * @return int
     */
    public function getWordCount()
    {
        return count($this->getWords());
    }


    /**
     * Returns the current iterator word.
     *
     * @param bool $bWithComma
     * @return mixed
     */
    public function getWord($bWithComma = false)
    {
        return str_replace(!$bWithComma ? ',' : '', '', $this->getWords()[$this->getIterator()]);
    }


    /**
     * Returns any applicable rules for the current word.
     *
     * @return array|mixed
     */
    public function getRule()
    {
        foreach ($this->getRules() as $rule) {
            if (in_array($this->getWord(), $rule['criteria'])) {
                return $rule;
            }
        }

        return [];
    }


    /**
     * Returns the previous word.
     *
     * @return mixed
     */
    public function getPreviousWord()
    {
        return $this->getWords()[$this->getIterator() - 1];
    }


    /**
     * Returns a series of word-specific rules.
     *
     * @return array
     */
    public function getRules()
    {
        return [
            [
                'criteria' => ['when', 'if'],
                'action' => function (Sentence $sentence) {
                    if (!$sentence->getIterator()) {
                        return;
                    }

                    if (in_array($sentence->getPreviousWord(), $sentence->getPrepositions())) {
                        return;
                    }

                    $verb = Verb::create()->addWord($sentence->getPreviousWord());

                    if ($sentence->getGrammar() && $sentence->getGrammar()->getWords()) {
                        $sentence->getGrammar()->popWord();

                        if ($sentence->getGrammar()->getWords()) {
                            $sentence->addSegment($sentence->getGrammar());
                        }

                        $sentence->setGrammar(Noun::create());
                    }

                    $sentence->addSegment($verb);
                }
            ]
        ];
    }


    /**
     * Renders an output of the sentence for debugging purposes.
     */
    public function debug()
    {
        var_dump($this->content);

        foreach ($this->getSegments() as $segment) {
            var_dump((string)$segment);
        }

        echo "\n";
    }


    /**
     * Returns a series of quantifier words.
     *
     * @return array
     */
    public function getQuantifiers()
    {
        return [
            'any', 'all', 'many', 'much', 'most', 'some', 'few', 'lot', 'more',
            'little', 'large', 'none', 'other', 'another', 'often', 'multiple'
        ];
    }


    /**
     * Returns a series of auxiliary verbs.
     *
     * @return array
     */
    public function getAuxiliaryVerbs()
    {
        return [
            'be', 'am', 'are', 'is', 'was', 'were', 'being', 'can', 'could', 'do',
            'did', 'does', 'doing', 'have', 'had', 'has', 'having', 'may', 'might',
            'must', 'shall', 'should', 'will', 'would', 'not',
        ];
    }


    /**
     * Returns a series of prepositions.
     *
     * @return array
     */
    public function getPrepositions()
    {
        return [
            'aboard', 'about', 'above', 'across', 'after', 'against', 'along',
            'amid', 'among', 'anti', 'around', 'as', 'at', 'before', 'behind',
            'below', 'beneath', 'beside', 'besides', 'between', 'beyond', 'but',
            'by', 'concerning', 'considering', 'despite', 'down', 'during', 'except',
            'excepting', 'excluding', 'following', 'for', 'from', 'in', 'inside',
            'into', 'like', 'minus', 'near', 'of', 'off', 'on', 'onto', 'opposite',
            'outside', 'over', 'past', 'per', 'plus', 'regarding', 'round', 'save',
            'since', 'than', 'through', 'to', 'toward', 'towards', 'under',
            'underneath', 'unlike', 'until', 'up', 'upon', 'versus', 'via', 'with',
            'within', 'without', 'because'
        ];
    }


    /**
     * Returns a series of determiners.
     *
     * @return array
     */
    public function getDeterminers()
    {
        return [
            'also', 'too', 'this', 'that', 'these', 'those', 'my', 'your', 'his', 'her',
            'its', 'our', 'their', 'all', 'both', 'half', 'either', 'neither', 'each',
            'every', 'other', 'another', 'such', 'what', 'which', 'rather', 'quite',
            'became', 'become', 'becoming'
        ];
    }


    /**
     * Returns a series of articles.
     *
     * @return array
     */
    public function getArticles()
    {
        return [
            'a', 'an', 'the'
        ];
    }


    /**
     * Returns the current grammar segment.
     *
     * @return Grammar
     */
    public function getGrammar()
    {
        return $this->segment;
    }


    /**
     * Assigns the current grammar segment.
     *
     * @param null $segment
     * @return $this
     */
    public function setGrammar($segment = null)
    {
        $this->segment = $segment;

        return $this;
    }


    /**
     * Indicates whether or not the current word is possessive.
     *
     * @return bool
     */
    public function isPossessive()
    {
        return strpos($this->getWord(), "'") !== false;
    }


    /**
     * Attempts to guess whether or not a value is a verb.
     *
     * @return bool
     */
    public function isGuessedVerb()
    {
        return (substr($this->getWord(), -2) === 'ed' && !in_array(substr($this->getWord(), -3), ['eed', 'ied'])) ||
            substr($this->getWord(), -3) === 'ize';
    }


    /**
     * Attempts to guess whether or not the value is an adverb.
     *
     * @return bool
     */
    public function isGuessedAdverb()
    {
        return substr($this->getWord(), -2) === 'ly';
    }


    /**
     * Attempts to guess whether or not the value is an adjective.
     *
     * @return bool
     */
    public function isGuessedAdjective()
    {
        return substr($this->getWord(), -5) === 'ional' || (strlen($this->getWord()) > 5 && substr($this->getWord(), -4) === 'ing');
    }


    /**
     * Identifies whether or not the word is a conjunction.
     *
     * @return bool
     */
    public function isConjunction()
    {
        return in_array($this->getWord(), ['and', 'or']);
    }


    /**
     * Indicates whether or not the word is a quantifier.
     *
     * @return bool
     */
    public function isQuantifier()
    {
        return in_array($this->getWord(), $this->getQuantifiers()) || is_numeric($this->getWord());
    }


    /**
     * Indicates whether or not the word is an quantifier.
     *
     * @return bool
     */
    public function isArticle()
    {
        return in_array($this->getWord(), $this->getArticles());
    }


    /**
     * Indicates whether or not the word is an auxiliary verb.
     *
     * @return bool
     */
    public function isAuxiliaryVerb()
    {
        return in_array($this->getWord(), $this->getAuxiliaryVerbs());
    }


    /**
     * Indicates whether or not the word is a preposition.
     *
     * @param array $exceptions
     * @return bool
     */
    public function isPreposition($exceptions = [])
    {
        return in_array($this->getWord(), $this->getPrepositions()) && !in_array($this->getWord(), $exceptions);
    }


    /**
     * Indicates whether or not the word is a determiner.
     *
     * @return bool
     */
    public function isDeterminer()
    {
        return in_array($this->getWord(), $this->getDeterminers());
    }


    /**
     * Indicates whether or not the current word ends in a comma.
     *
     * @return bool
     */
    public function hasComma()
    {
        return substr($this->getWord(true), -1) === ',';
    }


    /**
     * Parses the sentence content.
     *
     * @return $this
     */
    public function parse()
    {
        if (!$this->getWordCount()) {
            return $this;
        }

        while ($this->getIterator() < $this->getWordCount() - 1) {
            $this->addIterator();

            // Ignore any quantifiers or numeric attributes.
            if ($this->isQuantifier()) {
                continue;
            }

            // Ignore possessive words.
            if ($this->isPossessive()) {
                continue;
            }

            if ($rule = $this->getRule()) {
                $rule['action']($this);
                continue;
            }

            // Determine whether or not we have a verb.
            if ($this->isGuessedVerb()) {
                $verb = Verb::create()->addWord($this->getWord());
                $this->addSegment($this->getGrammar())
                    ->addSegment($verb)
                    ->setGrammar(Noun::create());
                continue;
            }

            // Determine whether or not we have an adjective.
            if ($this->isGuessedAdverb()) {
                $adverb = Adverb::create()->addWord($this->getWord());
                $this->addSegment($this->getGrammar())
                    ->addSegment($adverb)
                    ->setGrammar(Noun::create());
                continue;
            }

            if ($this->isGuessedAdjective()) {
                $adjective = Adjective::create()->addWord($this->getWord());
                $this->addSegment($adjective)
                    ->setGrammar(Noun::create());
                break;
            }

            if ($this->getGrammar()) {
                switch (true) {
                    case $this->getGrammar() instanceof Noun:
                        if ($this->isConjunction()) {
                            $this->addSegment($this->getGrammar())
                                ->setGrammar(Noun::create());
                            break;
                        }

                        // If we hit a differing sentence component, we'll terminate the noun discovery process.
                        if ($this->isArticle() || $this->isAuxiliaryVerb() || $this->isPreposition() || $this->isDeterminer() || $this->hasComma()) {
                            if ($this->hasComma()) {
                                $this->getGrammar()->addWord($this->getWord());
                            }

                            $this->addSegment($this->getGrammar())->setGrammar(Noun::create());
                            break;
                        }

                        $this->getGrammar()->addWord($this->getWord());
                        break;
                }
            } else {
                switch (true) {
                    // Since articles or auxiliary verbs indicate the start of a grammatical phrase, we can start to
                    // attempt building the entire segment series at this point.
                    //
                    case $this->isArticle() || $this->isAuxiliaryVerb() || $this->isPreposition() || $this->isDeterminer() || $this->hasComma():
                        $this->setGrammar(Noun::create());

                        if (!$this->getIterator() && $this->hasComma()) {
                            $this->getGrammar()->addWord($this->getWord());
                        }
                        break;

                    // If the first word doesn't meet our other criteria, we can assume that the first
                    // component is itself part of a noun / ie. the subject of the sentence.
                    //
                    case !$this->getIterator():
                        $this->setGrammar(Noun::create()->addWord($this->getWord()));
                        break;

                    default:
                        break;
                }
            }
        }

        // Add the remaining segment, if applicable.
        if ($this->getGrammar()->getWords()) {
            $this->addSegment($this->getGrammar())->setGrammar();
        }

        return $this;
    }
}

$dictionary = new Dictionary();

// Read all individual sentences from the document.
foreach ($document->getElementsByTagName('p') as $paragraph) {
    $dictionary->addParagraph($paragraph->nodeValue);
}


var_dump($dictionary->getNounOccurrences());
die();