<?php

/**
 * DEPRECATED one-off script.
 *
 * DO NOT use plain password_hash() here — Myth\Auth hashes via
 * base64(sha384(password)) then bcrypt. Plain bcrypt breaks login.
 *
 * Use instead:
 *   php spark opresent:fix-passwords
 *   php spark students:update-passwords
 */
fwrite(STDERR, "Use: php spark opresent:fix-passwords\n");
exit(1);