<?php
/**
 * Isolated BeYourLover Newsletter Canary Dev Safety contract.
 * Never opens a network connection, invokes wp_mail or sends any email.
 * Run: php tools/dev/tests/newsletter-one-mail-safety-smoke.php
 */
declare(strict_types=1);

$cases = [
    'no_permit', 'approved_once', 'already_consumed', 'expired', 'missing_backup_file', 'tampered_backup', 'wrong_backup_path',
    'future', 'long_lifetime', 'wrong_site', 'wrong_environment',
    'not_cli', 'prior_denial', 'unapproved', 'missing_backup_hash',
    'wrong_recipient', 'extra_to', 'headers_cc', 'headers_bcc',
    'wrong_subject', 'wrong_body', 'bad_token', 'same_token',
    'extra_field', 'wrong_purpose', 'wrong_ticket', 'wrong_mode',
    'max_two', 'old_ticket', 'wrong_site_ticket',
];
$argvCount = count($argv);
if ($argvCount === 1) {
    foreach ($cases as $name) {
        $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($name);
        passthru($command, $status);
        if ($status !== 0) { exit($status); }
    }
    if (!function_exists('proc_open')) { throw new RuntimeException('Process spawning required for concurrency smoke'); }
    $root = dirname(__DIR__, 3);
    $shared = sys_get_temp_dir().'/newsletter-mail-race-'.bin2hex(random_bytes(6));
    if (!mkdir($shared, 0700)) { throw new RuntimeException('Race directory unavailable'); }
    $shared=realpath($shared);
    $source = file_get_contents($root.'/dev/mu-plugins/byl-dev-safety.php');
    if (!is_string($source) || !str_contains($source, '/home/u247343654/byl-v3-staging')) {
        throw new RuntimeException('Missing expected Dev safety source anchor');
    }
    $source = str_replace('/home/u247343654/byl-v3-staging', $shared, $source);
    $source = str_replace('/home/u247343654/backups', $shared, $source);
    file_put_contents($shared.'/safety.php', $source);
    mkdir($shared.'/newsletter-m37-dev-pretest-20261010T081209Z-ABC123', 0700);
    file_put_contents($shared.'/newsletter-m37-dev-pretest-20261010T081209Z-ABC123/dev-pretest.sql.gz', str_repeat('B', 2048));
    $ticket = newsletter_test_ticket();
    $ticket['backup_path'] = $shared.'/newsletter-m37-dev-pretest-20261010T081209Z-ABC123/dev-pretest.sql.gz';
    file_put_contents($shared.'/newsletter-one-mail-permit.json', json_encode($ticket));
    chmod($shared.'/newsletter-one-mail-permit.json', 0600);
    $processes = [];
    for ($i = 0; $i < 2; $i++) {
        $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' race_worker '.escapeshellarg($shared);
        $p = proc_open($command, [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
        if (!is_resource($p)) { throw new RuntimeException('Unable to run race worker'); }
        fclose($pipes[0]);
        $processes[] = [$p, $pipes];
    }
    $outputs = [];
    foreach ($processes as [$p, $pipes]) {
        $out = trim((string)stream_get_contents($pipes[1]));
        $err = trim((string)stream_get_contents($pipes[2]));
        fclose($pipes[1]);fclose($pipes[2]);
        $status = proc_close($p);
        if ($status !== 0) { throw new RuntimeException('Race worker failure '.$err); }
        $outputs[] = $out;
    }
    sort($outputs);
    if ($outputs !== ['RACE_DENIED','RACE_GRANTED']) {
        throw new RuntimeException('Race did not grant exactly one send: '.implode(',', $outputs));
    }
    $final=json_decode((string)file_get_contents($shared.'/newsletter-one-mail-permit.json'), true);
    if (($final['state']??null)!=='consumed') { throw new RuntimeException('Race ticket not consumed'); }
    foreach (glob($shared.'/newsletter-m37-dev-pretest-20261010T081209Z-ABC123/*') ?: [] as $file) { unlink($file); }
    rmdir($shared.'/newsletter-m37-dev-pretest-20261010T081209Z-ABC123');
    foreach (glob($shared.'/*') ?: [] as $file) { unlink($file); }
    rmdir($shared);
    echo "NEWSLETTER_ONE_MAIL_SAFETY=PASS ".count($cases)." negative/positive cases + cross-process race\n";
    exit(0);
}

function newsletter_test_ticket(): array {
    return [
        'purpose'=>'newsletter-confirmation-canary',
        'site'=>'https://dev.beyourlover.com',
        'state'=>'approved',
        'approved_by_owner'=>true,
        'max_messages'=>1,
        'ticket_id'=>str_repeat('a',32),
        'backup_sha256'=>hash('sha256', str_repeat('B', 2048)),
        'backup_path'=>'/home/u247343654/backups/newsletter-m37-dev-pretest-20261010T081209Z-ABC123/dev-pretest.sql.gz',
        'recipient_sha256'=>hash('sha256','owner@example.test'),
        'issued_at'=>time()-10,
        'expires_at'=>time()+180,
    ];
}
function newsletter_test_message(string $token1, string $token2): string {
    return "Please confirm your email subscription by visiting:\n".
        "https://dev.beyourlover.com/?yby_newsletter_action=confirm&token=".$token1.
        "\n\nIf you did not request this, ignore this email. You can unsubscribe using:\n".
        "https://dev.beyourlover.com/?yby_newsletter_action=unsubscribe&token=".$token2;
}
function newsletter_test_mail(): array {
    return [
        'to'=>'owner@example.test',
        'subject'=>'Confirm your newsletter subscription',
        'message'=>newsletter_test_message(str_repeat('c',64),str_repeat('d',64)),
        'headers'=>[],
        'attachments'=>[],
        'embeds'=>[],
    ];
}
$scenario = $argv[1];
$race = $scenario === 'race_worker';
$root = dirname(__DIR__,3);
$tmp = $race ? ($argv[2] ?? '') :
    sys_get_temp_dir().'/newsletter-one-mail-security-'.bin2hex(random_bytes(6));
if (!$race && !mkdir($tmp, 0700)) { throw new RuntimeException('Fixture mkdir fail'); }
$tmp=realpath($tmp);
if (!$race) {
    $source = file_get_contents($root.'/dev/mu-plugins/byl-dev-safety.php');
    if (!is_string($source) || !str_contains($source, '/home/u247343654/byl-v3-staging')) {
        throw new RuntimeException('Source mismatch');
    }
    $source = str_replace('/home/u247343654/byl-v3-staging', $tmp, $source);
    $source = str_replace('/home/u247343654/backups', $tmp, $source);
    file_put_contents($tmp.'/safety.php', $source);
}
$GLOBALS['filters'] = [];
function add_filter($name, $cb, $priority=10, $accepted=1): void {
    $GLOBALS['filters'][$name][] = ['callback'=>$cb,'priority'=>$priority,'accepted'=>$accepted];
}
function add_action($name, $callback, $priority=10, $accepted=1): void {
    $GLOBALS['filters'][$name][] = ['callback'=>$callback,'priority'=>$priority,'accepted'=>$accepted];
}
function wp_get_environment_type(): string {
    return $GLOBALS['testCase'] === 'wrong_environment' ? 'production' : 'staging';
}
function home_url($suffix = ''): string {
    return ($GLOBALS['testCase']==='wrong_site'?'https://www.beyourlover.com':'https://dev.beyourlover.com').$suffix;
}
function is_email($value) { return filter_var($value,FILTER_VALIDATE_EMAIL) ?: false; }
function wp_json_encode($value,$flags=0) { return json_encode($value,$flags); }
$GLOBALS['testCase']=$scenario;
if ($scenario !== 'not_cli') { define('WP_CLI',true); }
define('ABSPATH', '/offline-fixture/');
require $tmp.'/safety.php';
$filters = array_values(array_filter($GLOBALS['filters']['pre_wp_mail'] ?? [], fn($v) => $v['accepted']===2));
if ($scenario === 'wrong_environment') {
    // The MU module never runs outside staging: it must install no hooks.
    if (count($filters)!==0) { throw new RuntimeException('Production modified by staging plugin'); }
    foreach (glob($tmp.'/*') ?: [] as $file) { unlink($file); }
    rmdir($tmp);
    echo "PASS wrong_environment (no staging filters loaded)".PHP_EOL;
    exit(0);
}
if (count($filters)!==1) { throw new RuntimeException('Expected exact fail-closed mail filter'); }
$fn=$filters[0]['callback'];
if ($race) {
    echo $fn(null,newsletter_test_mail())===null ? 'RACE_GRANTED' : 'RACE_DENIED';
    exit(0);
}
$permit=newsletter_test_ticket();
$mail=newsletter_test_mail();
$backupDir=$tmp.'/newsletter-m37-dev-pretest-20261010T081209Z-ABC123';
mkdir($backupDir, 0700);
file_put_contents($backupDir.'/dev-pretest.sql.gz', str_repeat('B',2048));
$permit['backup_path']=$backupDir.'/dev-pretest.sql.gz';
$allow=$scenario==='approved_once';
switch ($scenario) {
    case 'expired': $permit['expires_at']=time()-1;break;
    case 'missing_backup_file': unlink($backupDir.'/dev-pretest.sql.gz');break;
    case 'tampered_backup':file_put_contents($backupDir.'/dev-pretest.sql.gz','MODIFIED'.str_repeat('B',2048));break;
    case 'wrong_backup_path':$permit['backup_path']=$tmp.'/elsewhere.sql.gz';break;
    case 'future': $permit['issued_at']=time()+1;break;
    case 'long_lifetime': $permit['expires_at']=$permit['issued_at']+301;break;
    case 'wrong_site_ticket': $permit['site']='https://www.beyourlover.com';break;
    case 'unapproved': $permit['approved_by_owner']=false;break;
    case 'missing_backup_hash': unset($permit['backup_sha256']);break;
    case 'wrong_recipient': $mail['to']='visitor@example.test';break;
    case 'extra_to': $mail['to']=['owner@example.test','other@example.test'];break;
    case 'headers_cc': $mail['headers']=['Cc: other@example.test'];break;
    case 'headers_bcc': $mail['headers']=['Bcc: other@example.test'];break;
    case 'wrong_subject': $mail['subject']='Your receipt';break;
    case 'wrong_body': $mail['message']='payment/marketing payload';break;
    case 'bad_token': $mail['message']=newsletter_test_message('invalid',str_repeat('d',64));break;
    case 'same_token': $mail['message']=newsletter_test_message(str_repeat('c',64),str_repeat('c',64));break;
    case 'extra_field': $mail['extra']='unreviewed';break;
    case 'wrong_purpose': $permit['purpose']='payment';break;
    case 'wrong_ticket': $permit['ticket_id']='notvalid';break;
    case 'wrong_mode': $permit['state']='revoked';break;
    case 'max_two': $permit['max_messages']=2;break;
    case 'old_ticket': $permit['issued_at']=time()-301;break;
    case 'already_consumed': $permit['state']='consumed';break;
    case 'no_permit':break;
    case 'not_cli':break;
    case 'wrong_site':break;
    case 'wrong_environment':break;
    case 'prior_denial':break;
    case 'approved_once':break;
    default:throw new RuntimeException('Unexpected test case');
}
$path=$tmp.'/newsletter-one-mail-permit.json';
if ($scenario !== 'no_permit') {
    file_put_contents($path,json_encode($permit));
    chmod($path,0600);
}
$pre = $scenario==='prior_denial' ? false : null;
$actual=$fn($pre,$mail);
if ($allow !== ($actual===null)) {
    throw new RuntimeException('Wrong mail decision '.$scenario);
}
if ($allow) {
    $mailerHooks=$GLOBALS['filters']['phpmailer_init'] ?? [];
    if (count($mailerHooks)!==1) { throw new RuntimeException('Missing secondary mailer guard'); }
    $mailerGuard=$mailerHooks[0]['callback'];
    $makeMailer=static function(array $to, array $cc=[], array $bcc=[], string $subject='Confirm your newsletter subscription'): object {
        return new class($to,$cc,$bcc,$subject) {
            public string $Subject;
            private array $to;
            private array $cc;
            private array $bcc;
            public function __construct(array $to,array $cc,array $bcc,string $subject){
                $this->to=$to;$this->cc=$cc;$this->bcc=$bcc;$this->Subject=$subject;
            }
            public function getToAddresses():array{return $this->to;}
            public function getCcAddresses():array{return $this->cc;}
            public function getBccAddresses():array{return $this->bcc;}
            public function getReplyToAddresses():array{return [];}
            public function getAttachments():array{return [];}
        };
    };
    $mailerGuard($makeMailer([['owner@example.test','']]));
    foreach ([
        $makeMailer([['owner@example.test',''], ['attacker@example.test','']]),
        $makeMailer([['owner@example.test','']], [['attacker@example.test','']]),
        $makeMailer([['owner@example.test','']], [], [['attacker@example.test','']]),
        $makeMailer([['owner@example.test','']], [], [], 'Your order receipt'),
    ] as $badMailer) {
        try {
            $mailerGuard($badMailer);
            throw new RuntimeException('Transport guard failed to reject modified PHPMailer');
        } catch (RuntimeException $e) {
            if ($e->getMessage()==='Transport guard failed to reject modified PHPMailer') { throw $e; }
        }
    }
    if ($fn(null,$mail)!==false) { throw new RuntimeException('Second send allowed'); }
    $saved=json_decode((string)file_get_contents($path),true);
    if (($saved['state']??null)!=='consumed' || !is_int($saved['consumed_at']??null)) {
        throw new RuntimeException('One-use ticket not consumed');
    }
} elseif ($scenario!=='no_permit') {
    $saved=json_decode((string)file_get_contents($path),true);
    if (($saved['state']??null)!==($permit['state']??null)) {
        throw new RuntimeException('Denied request consumed ticket: '.$scenario);
    }
}
foreach (glob($backupDir.'/*') ?: [] as $file) { unlink($file); }
rmdir($backupDir);
foreach (glob($tmp.'/*') ?: [] as $file) { unlink($file); }
rmdir($tmp);
echo "PASS ".$scenario.PHP_EOL;
