#!/usr/bin/env python3
# Manual PHP 7.4 downgrades that Rector cannot do. Run from the fork root.
import glob, re, shutil, subprocess, sys

here = sys.argv[1]


def ed(p, a, b):
    s = open(p).read()
    if a not in s:
        raise SystemExit(f'{p}: pattern not found: {a[:60]!r}')
    open(p, 'w').write(s.replace(a, b))


# enums -> singleton classes / string constants
shutil.copy(f'{here}/Channel.php', 'src/Contracts/Lexer/Channel.php')
shutil.copy(f'{here}/Mode.php', 'src/Parser/Analysis/Mode.php')
for p in glob.glob('src/**/*.php', recursive=True):
    if p.endswith('Contracts/Lexer/Channel.php'):
        continue
    s = open(p).read()
    o = s
    s = s.replace('Channel::DEFAULT', 'Channel::Default()')
    s = re.sub(r'\bChannel::(Default|Hidden|Unknown|EndOfInput)\b(?!\()', r'Channel::\1()', s)
    if s != o:
        open(p, 'w').write(s)

p = 'src/Lexer/Token/EndOfInputToken.php'
ed(p, """    /**
     * Note: This "@var" docblock is required because the php-cs-fixer
     *       contains a bug that can remove the dependency as unused.
     *
     * @var ChannelInterface
     */
    public const TOKEN_CHANNEL = Channel::EndOfInput();

""", '')
ed(p, """            name: self::TOKEN_CHANNEL->name,
            channel: self::TOKEN_CHANNEL,""", """            name: Channel::EndOfInput()->name,
            channel: Channel::EndOfInput(),""")
ed(p, 'use Phplrt\\Contracts\\Lexer\\ChannelInterface;\n', '')

p = 'src/Lexer/Lexer.php'
ed(p, """    /**
     * The channels a lexer says nothing about are not reported.
     *
     * @var non-empty-list<ChannelInterface>
     */
    public const DEFAULT_SKIP_CHANNELS = [
        Channel::Hidden(),
    ];
""", """    /**
     * The channels a lexer says nothing about are not reported.
     *
     * Note: Upstream this is a constant, which cannot hold objects on PHP 7.4.
     *
     * @return non-empty-list<ChannelInterface>
     */
    public static function getDefaultSkipChannels(): array
    {
        return [Channel::Hidden()];
    }
""")
ed(p, """        iterable $skip = self::DEFAULT_SKIP_CHANNELS,
    ) {
        $this->excluded = self::formatLexerSkippedTokens($skip);""", """        ?iterable $skip = null,
    ) {
        $this->excluded = self::formatLexerSkippedTokens($skip ?? self::getDefaultSkipChannels());""")
ed(p, '@param iterable<mixed, ChannelInterface> $skip\n', '@param iterable<mixed, ChannelInterface>|null $skip\n')

p = 'src/Parser/Parser.php'
s = open(p).read()
s = s.replace('Mode $mode = Mode::Tolerant)', '?Mode $mode = null)')
s = s.replace("""        $result = $this->trace($source);

        if (!$result instanceof FailureTracingResult) {""", """        $mode ??= Mode::Tolerant();
        $result = $this->trace($source);

        if (!$result instanceof FailureTracingResult) {""", 1)
s = re.sub(r'\bMode::(Tolerant|SyntaxCheck)\b(?!\()', r'Mode::\1()', s)
s = s.replace('($mode is Mode::Tolerant()', '($mode is Mode::Tolerant|null').replace('($mode is Mode::SyntaxCheck() ?', '($mode is Mode::SyntaxCheck ?')
open(p, 'w').write(s)

p = 'src/Parser/Exception/MessagePlaceholder.php'
s = open(p).read()
s = s.replace('enum MessagePlaceholder: string\n{', """/**
 * Note: Upstream this is a string-backed enum. This fork supports PHP 7.4,
 *       so the cases are string constants of the same names.
 */
final class MessagePlaceholder\n{""")
s = re.sub(r"    case (\w+) = '(\w+)';", r"    public const \1 = '\2';", s)
s = s.replace("""    public static function names(): array
    {
        return \\array_column(self::cases(), 'value');
    }""", """    public static function names(): array
    {
        return [
            self::Token,
            self::Name,
            self::Value,
            self::Offset,
            self::Line,
            self::Column,
            self::Expected,
            self::ExpectedList,
        ];
    }

    /**
     * @return non-empty-string|null
     */
    public static function tryFrom(string $name): ?string
    {
        return \\in_array($name, self::names(), true) ? $name : null;
    }""")
open(p, 'w').write(s)

# WeakMap does not exist on PHP 7.4
p = 'src/Parser/Internal/MessageInterpolator.php'
ed(p, '     * @var \\WeakMap<TokenInterface, PositionInterface>', '     * Note: Upstream this is a WeakMap, which does not exist on PHP 7.4.\n     *\n     * @var \\SplObjectStorage<TokenInterface, PositionInterface>')
ed(p, '\\WeakMap $positions;', '\\SplObjectStorage $positions;')
ed(p, 'new \\WeakMap()', 'new \\SplObjectStorage()')

# "new" in static variable initializers is PHP 8.3+
p = 'src/Lexer/Token/Token.php'
ed(p, """        /** @var PrettyTokenPrinter $printer */
        static $printer = new PrettyTokenPrinter();
""", """        /** @var PrettyTokenPrinter|null $printer */
        static $printer = null;
        $printer ??= new PrettyTokenPrinter();
""")

# Not a downgrade: optimisations of the recursive descent interpreter, measured
# on the PHPDoc grammar of phpstan/phpdoc-parser and on the regex grammar of
# phpstan/phpstan-src (about 6% fewer instructions, identical results):
#  - the parser reads the tokens of the buffer in place instead of calling
#    ArrayBuffer::next() and ::seek();
#  - an alternation that leaves nothing behind and is predicted to read a
#    single production is read as that production, without a call;
#  - the terminals a sequence cannot go without are read in place;
#  - TraceReducer::merge() is written into the reduction loop.
# The patch is written against the upstream sources, before Rector runs.
subprocess.run(['git', 'apply', f'{here}/interpreter.patch'], check=True)
