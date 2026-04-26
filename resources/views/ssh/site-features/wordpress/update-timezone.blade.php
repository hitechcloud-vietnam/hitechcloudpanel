set -e

wp --path={{ $path }} option update timezone_string '{{ $timezone }}'
wp --path={{ $path }} option update gmt_offset ''
