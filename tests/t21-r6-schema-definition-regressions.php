<?php
$schema = file_get_contents( dirname( __DIR__ ) . '/includes/class-wca-schema.php' );
if ( false === $schema ) {
    fwrite( STDERR, "T21 R6 FAIL: schema source unreadable\n" );
    exit( 1 );
}
$checks = array(
    'full column metadata query' => "SHOW FULL COLUMNS FROM",
    'column type verification' => 'wca_schema_column_type_mismatch',
    'column nullability verification' => 'wca_schema_column_nullability_mismatch',
    'column default verification' => 'wca_schema_column_default_mismatch',
    'column extra verification' => 'wca_schema_column_extra_mismatch',
    'index uniqueness verification' => 'wca_schema_index_uniqueness_mismatch',
    'index ordered-column verification' => 'wca_schema_index_columns_mismatch',
    'unsigned type preservation' => "preg_match( '/^\\s*unsigned\\b/i', $tail )",
);
foreach ( $checks as $label => $needle ) {
    if ( false === strpos( $schema, $needle ) ) {
        fwrite( STDERR, "T21 R6 FAIL: {$label}\n" );
        exit( 1 );
    }
}
if ( false === strpos( $schema, "'Seq_in_index'" ) || false === strpos( $schema, "'Non_unique'" ) || false === strpos( $schema, "'Column_name'" ) ) {
    fwrite( STDERR, "T21 R6 FAIL: index metadata is not structurally inspected\n" );
    exit( 1 );
}
if ( false === strpos( $schema, "'Type'" ) || false === strpos( $schema, "'Null'" ) || false === strpos( $schema, "'Default'" ) || false === strpos( $schema, "'Extra'" ) ) {
    fwrite( STDERR, "T21 R6 FAIL: full column metadata is not structurally inspected\n" );
    exit( 1 );
}
echo "T21 R6 schema-definition structural regression checks passed.\n";
