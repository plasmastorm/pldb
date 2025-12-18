<?php
if (!defined('ABSPATH')) exit;

function pldb_sanitize_unslash($value) {
    return wp_unslash(sanitize_text_field($value));
}

function pldb_build_search_url($base_url, $search_term) {
    $params = [];
    $parsed = parse_url($base_url);
    if (isset($parsed['query'])) parse_str($parsed['query'], $params);
    $params['pldb_search'] = rawurlencode($search_term);
    return esc_url(add_query_arg($params, strtok($base_url, '?')));
}

function pldb_get_base_url($remove_params = []) {
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    $url = remove_query_arg($remove_params, $url);
    if (isset($_GET['p'])) $url = add_query_arg('p', $_GET['p'], $url);
    if (isset($_GET['page_id'])) $url = add_query_arg('page_id', $_GET['page_id'], $url);
    return $url;
}

function pldb_get_pagination_params($param_name, $per_page = null) {
    if (!$per_page) $per_page = PLDB::ITEMS_PER_PAGE;
    $page = isset($_GET[$param_name]) ? max(1, intval($_GET[$param_name])) : 1;
    $offset = ($page - 1) * $per_page;
    return [$per_page, $page, $offset];
}

function pldb_build_pagination($total, $per_page, $page, $base_url, $param = 'pldb_paged', $range = null) {
    if (!$range) $range = PLDB::PAGINATION_RANGE;
    $pages = ceil($total / $per_page);
    if ($pages <= 1) return '';

    $params = $_GET;
    unset($params[$param]);
    $html = '<nav aria-label="Pagination" class="pldb-pagination">';

    if ($page > 1) {
        $prev = add_query_arg(array_merge($params, [$param => $page - 1]), $base_url);
        $html .= '<a href="'.esc_url($prev).'" class="pldb-page-link" aria-label="Go to previous page">« Previous</a>';
    }

    for ($i = 1; $i <= $pages; $i++) {
        $show = ($i == 1 || $i == $pages || ($i >= $page - $range && $i <= $page + $range));
        if ($show) {
            if ($i == $page) {
                $html .= '<span class="pldb-page-current" aria-current="page">'.$i.'</span>';
            } else {
                $url = add_query_arg(array_merge($params, [$param => $i]), $base_url);
                $html .= '<a href="'.esc_url($url).'" class="pldb-page-link" aria-label="Go to page '.$i.'">'.$i.'</a>';
            }
        } elseif ($i == $page - $range - 1 || $i == $page + $range + 1) {
            $html .= '<span class="pldb-page-ellipsis" aria-hidden="true">...</span>';
        }
    }

    if ($page < $pages) {
        $next = add_query_arg(array_merge($params, [$param => $page + 1]), $base_url);
        $html .= '<a href="'.esc_url($next).'" class="pldb-page-link" aria-label="Go to next page">Next »</a>';
    }

    $html .= '</nav>';
    return $html;
}

function pldb_generate_link($type, $value, $row) {
    if (!$value) return false;

    switch ($type) {
        case 'suggester_list':
            $names = array_filter(array_map('trim', explode(',', $value)));
            $links = [];
            foreach ($names as $n) {
                $href = pldb_build_search_url(pldb_get_base_url(['pldb_search']), $n);
                $links[] = '<a href="'.$href.'">'.esc_html($n).'</a>';
            }
            return implode(', ', $links);

        case 'suggester_search':
        case 'artist_search':
        case 'track_search':
            $href = pldb_build_search_url(pldb_get_base_url(['pldb_search']), $value);
            return '<a href="'.$href.'">'.esc_html($value).'</a>';

        case 'show_archive':
            if ($row->archivelink) {
                $label = esc_attr($value.' (opens in new tab)');
                return '<a href="'.esc_url($row->archivelink).'" target="_blank" rel="noopener noreferrer" aria-label="'.$label.'">'.esc_html($value).' ↗</a>';
            }
            return false;

        default:
            return false;
    }
}

function pldb_build_html_table($columns, $data, $title = '', $total = null, $page = 1, $per_page = 20, $prefix = '') {
    $sort_p = $prefix ? 'pldb_'.$prefix.'_sort' : 'pldb_sort';
    $dir_p = $prefix ? 'pldb_'.$prefix.'_dir' : 'pldb_dir';
    $field = isset($_GET[$sort_p]) ? sanitize_key($_GET[$sort_p]) : '';
    $dir = isset($_GET[$dir_p]) && $_GET[$dir_p] === 'asc' ? 'asc' : 'desc';

    $paged = ($total !== null && $total > count($data));
    if ($paged) {
        $start = (($page - 1) * $per_page) + 1;
        $end = min($page * $per_page, $total);
        $count = $total;
    } else {
        $count = count($data);
    }

    $html = '';

    $html .= '<table class="pldb-table">';
    
    if ($title) {
        $display = $paged ? " ({$start}-{$end} of {$count})" : " ({$count})";
        $html .= '<caption>'.esc_html($title.$display).'</caption>';
    }

    $html .= '<thead><tr>';
    foreach ($columns as $k => $info) {
        $label = is_array($info) ? $info['label'] : $info;
        $sortable = is_array($info) && isset($info['sortable']) && $info['sortable'];
        $aria_sort = '';
        if ($sortable && $field === $k) {
            $aria_sort = ' aria-sort="'.($dir === 'asc' ? 'ascending' : 'descending').'"';
        }
        $html .= '<th scope="col"'.$aria_sort.'>';
        if ($sortable) {
            $new_dir = ($field === $k && $dir === 'asc') ? 'desc' : 'asc';
            $href = add_query_arg([$sort_p => $k, $dir_p => $new_dir], pldb_get_base_url([$sort_p, $dir_p]));
            $current = ($field === $k) ? $dir : '';
            $label_text = 'Sort by '.esc_attr($label);
            if ($current) {
                $label_text .= ' (currently '.($dir === 'asc' ? 'ascending' : 'descending').', click to sort '.($new_dir === 'asc' ? 'ascending' : 'descending').')';
            }
            $html .= '<a href="'.esc_url($href).'" class="pldb-sort" aria-label="'.esc_attr($label_text).'"'.($current ? ' data-dir="'.esc_attr($current).'"' : '').'>'.esc_html($label).'</a>';
        } else {
            $html .= esc_html($label);
        }
        $html .= '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($columns as $k => $info) {
            $value = property_exists($row, $k) ? $row->$k : '';
            $html .= '<td>';
            if (is_array($info) && isset($info['link_type'])) {
                $link = pldb_generate_link($info['link_type'], $value, $row);
                $html .= $link ?: esc_html($value);
            } else {
                $html .= esc_html($value);
            }
            $html .= '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

function pldb_process_suggesters($str) {
    $items = explode(',', $str);
    $result = [];
    foreach ($items as $item) {
        $item = trim($item);
        if (!$item) continue;
        if (strpos($item, '@') === 0 && strpos($item, '@', 1) !== false) {
            $item = substr($item, 0, strpos($item, '@', 1));
        }
        $result[] = $item;
    }
    return $result;
}

function pldb_normalize_suggester_display($results, $field) {
    if (!$results) return;
    foreach ($results as $result) {
        if ($result->$field) {
            $norm = pldb_process_suggesters($result->$field);
            $result->$field = implode(', ', array_unique($norm));
        }
    }
}
