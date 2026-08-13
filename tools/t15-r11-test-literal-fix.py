from pathlib import Path
p = Path('tests/fifteenth-twenty-review-regressions.php')
s = p.read_text()
replacements = {
    '"$from_version = (string) get_option( self::OPTION_DB_VERSION"': '"\\$from_version = (string) get_option( self::OPTION_DB_VERSION"',
    '"\'from_version\' => $from_version"': '"\'from_version\' => \\$from_version"',
    '"$template_service && ( ! $service_ref || ! hash_equals"': '"\\$template_service && ( ! \\$service_ref || ! hash_equals"',
    '"semantic_lock( \'arrival\', $id )"': '"semantic_lock( \'arrival\', \\$id )"',
    '"if ( is_wp_error( $raw ) || ! is_scalar( $raw ) )"': '"if ( is_wp_error( \\$raw ) || ! is_scalar( \\$raw ) )"',
}
for old, new in replacements.items():
    if old not in s:
        raise SystemExit('T15 literal target missing: ' + old)
    s = s.replace(old, new, 1)
p.write_text(s)
