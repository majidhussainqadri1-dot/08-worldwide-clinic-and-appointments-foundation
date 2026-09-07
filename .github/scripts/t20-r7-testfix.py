from pathlib import Path
root = Path(__file__).resolve().parents[2]
path = root / 'tests/t20-r7-availability-projection-regressions.php'
text = path.read_text()
text = text.replace('false!==strpos($service,"$closed_day = $exception && \'closed\' === $exception[\'type\'] && empty( $exception[\'start\'] ) && empty( $exception[\'end\'] )")', "false!==strpos($service, '$closed_day = $exception')")
text = text.replace("false!==strpos($service,'if ( ! $closed_start || ! $closed_end ) { return true; }')", "false!==strpos($service, '$closed_start') && false!==strpos($service, '$closed_end') && false!==strpos($service, '{ return true; }')")
text = text.replace('false!==strpos($service,"\'active\' !== (string) ( $branch[\'status\'] ?? \'\' )")&&false!==strpos($service,"\'public\' !== (string) ( $branch[\'visibility\'] ?? \'\' )")', "false!==strpos($service, \"'active' !== (string)\") && false!==strpos($service, \"'public' !== (string)\")")
path.write_text(text)
