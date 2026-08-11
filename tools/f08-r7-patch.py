from pathlib import Path

p = Path('includes/class-wca-repository.php')
s = p.read_text()
old = """\t\t$worker = sanitize_text_field( $worker ?: 'worker-' . substr( md5( wp_salt( 'nonce' ) . microtime( true ) ), 0, 12 ) );
\t\t$ids = (array) $wpdb->get_col( $wpdb->prepare( \"SELECT id FROM {$table} WHERE status IN ('pending','retry') AND next_attempt_at<=%s AND (locked_at IS NULL OR locked_at<%s) ORDER BY id ASC LIMIT %d\", self::now(), gmdate( 'Y-m-d H:i:s', time() - 300 ), $limit ) );
\t\t$claimed = array();
\t\tforeach ( $ids as $id ) {
\t\t\t$ok = $wpdb->update( $table, array( 'status' => 'processing', 'locked_at' => self::now(), 'locked_by' => $worker, 'updated_at' => self::now() ), array( 'id' => absint( $id ) ) );
\t\t\tif ( $ok ) {
\t\t\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE id=%d LIMIT 1\", absint( $id ) ), ARRAY_A );
\t\t\t\tif ( $row ) { $row['payload'] = self::decode( $row['payload_json'] ); unset( $row['payload_json'] ); $claimed[] = $row; }
\t\t\t}
\t\t}
"""
new = """\t\t$worker = sanitize_text_field( $worker ?: 'worker-' . substr( md5( wp_salt( 'nonce' ) . microtime( true ) ), 0, 12 ) );
\t\t$now = self::now();
\t\t$stale_before = gmdate( 'Y-m-d H:i:s', time() - 300 );
\t\t$ids = (array) $wpdb->get_col( $wpdb->prepare( \"SELECT id FROM {$table} WHERE status IN ('pending','retry') AND next_attempt_at<=%s AND (locked_at IS NULL OR locked_at<%s) ORDER BY id ASC LIMIT %d\", $now, $stale_before, $limit ) );
\t\t$claimed = array();
\t\tforeach ( $ids as $id ) {
\t\t\t$claimed_at = self::now();
\t\t\t$ok = $wpdb->query( $wpdb->prepare( \"UPDATE {$table} SET status='processing',locked_at=%s,locked_by=%s,updated_at=%s WHERE id=%d AND status IN ('pending','retry') AND next_attempt_at<=%s AND (locked_at IS NULL OR locked_at<%s)\", $claimed_at, $worker, $claimed_at, absint( $id ), $claimed_at, $stale_before ) );
\t\t\tif ( 1 === (int) $ok ) {
\t\t\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE id=%d AND status='processing' AND locked_by=%s LIMIT 1\", absint( $id ), $worker ), ARRAY_A );
\t\t\t\tif ( $row ) { $row['payload'] = self::decode( $row['payload_json'] ); unset( $row['payload_json'] ); $claimed[] = $row; }
\t\t\t}
\t\t}
"""
if old not in s:
    raise SystemExit('R7 outbox claim source block not found')
p.write_text(s.replace(old, new, 1))
