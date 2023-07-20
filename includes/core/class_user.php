<?php

class User {

    // GENERAL

    public static function user_info($d) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else return [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, last_name, email, phone FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'plot_id' => $row['plot_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => $row['phone']
            ];
        } else {
            return [
                'id' => 0,
                'access' => 0
            ];
        }
    }

    public static function users_list_plots($number) {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%".$number."%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }
    public static function user_list($d = []) {
        // vars
        $search = isset($d['search']) && trim($d['search']) ? $d['search'] : '';
        $offset = isset($d['offset']) && is_numeric($d['offset']) ? $d['offset'] : 0;
        $limit = 20;
        $items = [];
        // where
        $where = [];
        if ($search) {
            if(is_numeric($search)) $where[] = "phone LIKE '%".$search."%'";
            else {
                $where[] = "(email LIKE '%".$search."%' OR first_name LIKE '%".$search."%')";
            }
        }
        $where = $where ? "WHERE ".implode(" AND ", $where) : "";
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, last_name, email, phone, last_login
            FROM users ".$where." ORDER BY user_id LIMIT ".$offset.", ".$limit.";") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $items[] = [
                'id' => (int) $row['user_id'],
                'plots' => $row['plot_id'],
                'full_name' => $row['first_name'] . ' ' . $row['last_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'last_login' => date('d.m.Y H:i:s', $row['last_login'])
            ];
        }
        // paginator
        $q = DB::query("SELECT count(*) FROM users ".$where.";");
        $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
        $url = 'users?';
        if ($search) $url .= '&search='.$search;
        paginator($count, $offset, $limit, $url, $paginator);
        // output
        return ['items' => $items, 'paginator' => $paginator];
    }

    public static function user_edit_window($d = []): array {
//        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user', User::user_info($d));
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }


    /**
     * toDo
     */
    public static function users_fetch($d = []): array {
        $info = User::user_list($d);
        HTML::assign('users', $info['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
    }

    public static function user_edit_update($d = []) {
        // vars
        if(!$d['first_name'] || !$d['last_name'] || !$d['email'] || !$d['phone']) {
            http_response_code(551);
            return ['message' => 'you dont set all required fields'];
        }
        $d['email'] = strtolower($d['email']);
        $phoneArr = str_split($d['phone']);
        $phone = '';
        foreach ($phoneArr as $char) {
            if(is_numeric($char)) $phone .= $char;
        }
        if(!$phone) {
            http_response_code(551);
            return ['message' => 'phone number must contain digits'];
        }
        if(!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(551);
            return ['message' => 'email must be valid Email address'];
        }
        $d['phone'] = $phone;
        if($d['plot_id'] && !Plot::check_existing_plots($d['plot_id'])) {
            http_response_code(551);
            return ['message' => 'not all plots exists'];
        }
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
        unset($d['offset']);
        // update
        if ($user_id) {
            DB::prepareAndExec("UPDATE users SET first_name = :first_name, last_name = :last_name, phone = :phone, email = :email, plot_id = :plot_id  WHERE user_id=:user_id LIMIT 1;", $d) or die (DB::error());
        } else {
            unset($d['user_id']);
            DB::prepareAndExec("INSERT INTO users (
                first_name,
                last_name,
                phone,
                email,
                plot_id,
                updated,
                village_id,
                access,
                phone_code
            ) VALUES (
                :first_name,
                :last_name,
                :phone,
                :email,
                :plot_id,
                ".time().",
                1,
                1,
                1111
            );", $d) or die (DB::error());
        }
        // output
        return User::users_fetch(['offset' => $offset]);
    }

    public static function user_delete(array $d = []): array
    {
        $userId = $d['user_id'];
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
        DB::prepareAndExec('DELETE FROM users WHERE user_id = ?', [$userId]);
        return User::users_fetch(['offset' => $offset]);
    }

}
