<?php
/**
 * Independent verifier for a File 08 candidate artifact directory or ZIP.
 */

declare(strict_types=1);

final class File08_Candidate_Verifier {
	private const ROOT = 'worldwide-clinic';
	private const MAX_OUTER_FILES = 6;
	private const MAX_INNER_FILES = 64;
	private const MAX_FILE_BYTES = 30_000_000;

	/** @return array<string,mixed> */
	public static function verify(string $artifact, string $expected_outer_sha256 = ''): array {
		if (! class_exists('ZipArchive')) {
			throw new RuntimeException('PHP ZipArchive is required.');
		}
		if ($artifact === '' || str_contains($artifact, "\0") || is_link($artifact)) {
			throw new InvalidArgumentException('Artifact path is invalid.');
		}
		$outer_sha256 = '';
		if (is_dir($artifact)) {
			$files = self::directory_files($artifact);
			$mode = 'directory';
		} elseif (is_file($artifact)) {
			$real = realpath($artifact);
			if (! is_string($real)) {
				throw new RuntimeException('Unable to resolve artifact ZIP.');
			}
			$outer_sha256 = (string) hash_file('sha256', $real);
			if ($expected_outer_sha256 !== '' && ! hash_equals(strtolower($expected_outer_sha256), $outer_sha256)) {
				throw new RuntimeException('Outer artifact SHA-256 does not match.');
			}
			$files = self::outer_zip_files($real);
			$mode = 'zip';
		} else {
			throw new InvalidArgumentException('Artifact path does not exist.');
		}

		$bundle = self::identify($files);
		$result = self::verify_bundle($bundle);
		$result['artifact_mode'] = $mode;
		$result['outer_artifact_sha256'] = $outer_sha256;
		return $result;
	}

	/** @return array<string,string> */
	private static function directory_files(string $directory): array {
		$real = realpath($directory);
		if (! is_string($real) || ! is_dir($real)) {
			throw new RuntimeException('Unable to resolve artifact directory.');
		}
		$entries = scandir($real);
		if (! is_array($entries)) {
			throw new RuntimeException('Unable to list artifact directory.');
		}
		$files = array();
		foreach ($entries as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			if (! self::safe_outer_name($entry)) {
				throw new RuntimeException('Unsafe artifact filename: ' . $entry);
			}
			$path = $real . DIRECTORY_SEPARATOR . $entry;
			if (is_link($path) || ! is_file($path)) {
				throw new RuntimeException('Artifact directory may contain regular files only.');
			}
			$contents = file_get_contents($path);
			if (! is_string($contents) || strlen($contents) > self::MAX_FILE_BYTES) {
				throw new RuntimeException('Unable to read artifact file: ' . $entry);
			}
			$files[$entry] = $contents;
		}
		if ($files === array() || count($files) > self::MAX_OUTER_FILES) {
			throw new RuntimeException('Artifact directory file count is invalid.');
		}
		return $files;
	}

	/** @return array<string,string> */
	private static function outer_zip_files(string $path): array {
		$zip = new ZipArchive();
		if ($zip->open($path, ZipArchive::RDONLY) !== true) {
			throw new RuntimeException('Unable to open workflow artifact ZIP.');
		}
		try {
			if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_OUTER_FILES) {
				throw new RuntimeException('Workflow artifact entry count is invalid.');
			}
			$files = array();
			for ($index = 0; $index < $zip->numFiles; $index++) {
				$name = $zip->getNameIndex($index);
				$stat = $zip->statIndex($index);
				if (! is_string($name) || ! is_array($stat) || ! self::safe_outer_name($name) || isset($files[$name])) {
					throw new RuntimeException('Unsafe or duplicate workflow artifact entry.');
				}
				if (self::zip_entry_is_symlink($stat)) {
					throw new RuntimeException('Symbolic link detected in workflow artifact.');
				}
				$contents = $zip->getFromIndex($index);
				if (! is_string($contents) || strlen($contents) > self::MAX_FILE_BYTES) {
					throw new RuntimeException('Unable to read workflow artifact entry.');
				}
				$files[$name] = $contents;
			}
			return $files;
		} finally {
			$zip->close();
		}
	}

	/** @param array<string,string> $files @return array<string,string> */
	private static function identify(array $files): array {
		$zips = array();
		foreach (array_keys($files) as $name) {
			if (preg_match('/^08-worldwide-clinic-and-appointments-foundation-(\d+\.\d+\.\d+)-candidate\.zip$/', $name, $match) === 1) {
				$zips[$name] = $match[1];
			}
		}
		if (count($zips) !== 1) {
			throw new RuntimeException('Artifact must contain exactly one versioned File 08 candidate ZIP.');
		}
		$zip_name = (string) array_key_first($zips);
		$version = $zips[$zip_name];
		$base = '08-worldwide-clinic-and-appointments-foundation-' . $version . '-candidate';
		$expected = array($zip_name, $base . '.sha256', $base . '-manifest.json');
		sort($expected, SORT_STRING);
		$actual = array_keys($files);
		sort($actual, SORT_STRING);
		if ($actual !== $expected) {
			throw new RuntimeException('Artifact file set is not exact.');
		}
		return array(
			'version' => $version,
			'zip_name' => $zip_name,
			'zip_bytes' => $files[$zip_name],
			'checksum_bytes' => $files[$base . '.sha256'],
			'manifest_bytes' => $files[$base . '-manifest.json'],
		);
	}

	/** @param array<string,string> $bundle @return array<string,mixed> */
	private static function verify_bundle(array $bundle): array {
		$checksum = $bundle['checksum_bytes'];
		if (preg_match('/^([a-f0-9]{64})  ([A-Za-z0-9._-]+\.zip)\n?$/', $checksum, $match) !== 1
			|| ! hash_equals($bundle['zip_name'], $match[2])
		) {
			throw new RuntimeException('Detached checksum is invalid.');
		}
		$package_sha256 = hash('sha256', $bundle['zip_bytes']);
		if (! hash_equals($match[1], $package_sha256)) {
			throw new RuntimeException('Candidate ZIP SHA-256 does not match.');
		}
		$manifest = json_decode($bundle['manifest_bytes'], true);
		if (! is_array($manifest)
			|| ($manifest['schema_version'] ?? null) !== 1
			|| ($manifest['package'] ?? '') !== self::ROOT
			|| ($manifest['file_number'] ?? null) !== 8
			|| ($manifest['version'] ?? '') !== $bundle['version']
			|| ($manifest['public_clinic_contract'] ?? '') !== '1.0.0'
			|| ($manifest['staging_accepted'] ?? true) !== false
			|| ($manifest['production_accepted'] ?? true) !== false
		) {
			throw new RuntimeException('Detached manifest identity or acceptance state is invalid.');
		}
		if (preg_match('/^[a-f0-9]{40}$/', (string) ($manifest['commit_sha'] ?? '')) !== 1) {
			throw new RuntimeException('Manifest commit SHA is invalid.');
		}
		$inner = self::verify_inner($bundle['zip_bytes'], $bundle['manifest_bytes'], $manifest);
		return array(
			'verified' => true,
			'version' => $bundle['version'],
			'commit_sha' => $manifest['commit_sha'],
			'package_sha256' => $package_sha256,
			'payload_file_count' => $inner['count'],
			'payload_bytes' => $inner['bytes'],
			'public_clinic_contract' => '1.0.0',
			'staging_accepted' => false,
			'production_accepted' => false,
		);
	}

	/** @param array<string,mixed> $manifest @return array{count:int,bytes:int} */
	private static function verify_inner(string $zip_bytes, string $manifest_bytes, array $manifest): array {
		$temp = tempnam(sys_get_temp_dir(), 'file08-');
		if (! is_string($temp)) {
			throw new RuntimeException('Unable to create temporary file.');
		}
		try {
			file_put_contents($temp, $zip_bytes, LOCK_EX);
			$zip = new ZipArchive();
			if ($zip->open($temp, ZipArchive::RDONLY) !== true) {
				throw new RuntimeException('Unable to open inner candidate ZIP.');
			}
			try {
				$files = $manifest['files'] ?? null;
				if (! is_array($files) || $files === array() || count($files) > self::MAX_INNER_FILES) {
					throw new RuntimeException('Manifest payload map is invalid.');
				}
				$expected = array();
				foreach (array_keys($files) as $relative) {
					if (! is_string($relative) || ! self::safe_inner_relative($relative)) {
						throw new RuntimeException('Unsafe manifest path.');
					}
					$expected[] = self::ROOT . '/' . $relative;
				}
				$expected[] = self::ROOT . '/STAGING-MANIFEST.json';
				sort($expected, SORT_STRING);
				$actual = array();
				for ($index = 0; $index < $zip->numFiles; $index++) {
					$name = $zip->getNameIndex($index);
					$stat = $zip->statIndex($index);
					if (! is_string($name) || ! is_array($stat) || isset($actual[$name]) || self::zip_entry_is_symlink($stat)) {
						throw new RuntimeException('Unsafe, duplicate, or symbolic inner entry.');
					}
					$actual[$name] = true;
				}
				$actual_names = array_keys($actual);
				sort($actual_names, SORT_STRING);
				if ($actual_names !== $expected) {
					throw new RuntimeException('Inner candidate file set is not exact.');
				}
				if ($zip->getFromName(self::ROOT . '/STAGING-MANIFEST.json') !== $manifest_bytes) {
					throw new RuntimeException('Embedded and detached manifests differ.');
				}
				$total = 0;
				foreach ($files as $relative => $metadata) {
					$contents = $zip->getFromName(self::ROOT . '/' . $relative);
					if (! is_string($contents) || ! is_array($metadata)
						|| ! is_int($metadata['bytes'] ?? null)
						|| preg_match('/^[a-f0-9]{64}$/', (string) ($metadata['sha256'] ?? '')) !== 1
						|| strlen($contents) !== $metadata['bytes']
						|| ! hash_equals($metadata['sha256'], hash('sha256', $contents))
					) {
						throw new RuntimeException('Inner candidate payload mismatch: ' . $relative);
					}
					$total += $metadata['bytes'];
				}
				return array('count' => count($files), 'bytes' => $total);
			} finally {
				$zip->close();
			}
		} finally {
			@unlink($temp);
		}
	}

	private static function safe_outer_name(string $name): bool {
		return $name !== '' && ! str_contains($name, '/') && ! str_contains($name, '\\')
			&& ! str_contains($name, '..') && preg_match('/^[A-Za-z0-9._-]+$/', $name) === 1;
	}

	private static function safe_inner_relative(string $name): bool {
		return $name !== '' && ! str_starts_with($name, '/') && ! str_contains($name, '..')
			&& ! str_contains($name, '\\') && preg_match('#^[A-Za-z0-9._/-]+$#', $name) === 1;
	}

	/** @param array<string,mixed> $stat */
	private static function zip_entry_is_symlink(array $stat): bool {
		$attributes = (int) ($stat['external_attributes'] ?? 0);
		return (($attributes >> 16) & 0170000) === 0120000;
	}
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
	$options = getopt('', array('artifact:', 'artifact-sha256::'));
	try {
		$result = File08_Candidate_Verifier::verify(
			(string) ($options['artifact'] ?? ''),
			strtolower(trim((string) ($options['artifact-sha256'] ?? '')))
		);
		echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
	} catch (Throwable $exception) {
		fwrite(STDERR, 'FAILED: ' . $exception->getMessage() . PHP_EOL);
		exit(1);
	}
}
