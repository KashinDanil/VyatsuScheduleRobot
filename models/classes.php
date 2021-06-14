<?php
class InlineKeyboard {
    public $inline_keyboard;

    public function __construct($inline_keyboard) {
        $this->inline_keyboard = $inline_keyboard;
    }
}

class InlineKeyboardButton {
    public $text;

    public function __construct($text, $callback_data = "", $url = "") {
        $this->text = $text;
        if ($callback_data != "") {
            $this->callback_data = $callback_data;
        }
        if ($url != "") {
            $this->url = $url;
        }
    }
}

class ReplyKeyboardMarkup {
    public $keyboard;
    public $resize_keyboard;
    public $one_time_keyboard;

    public function __construct($keyboard, $resize_keyboard = false, $one_time_keyboard = false) {
        $this->keyboard = $keyboard;
        $this->resize_keyboard = $resize_keyboard;
        $this->one_time_keyboard = $one_time_keyboard;
    }
}

class ReplyKeyboardRemove {
    public $remove_keyboard = true;

    public function __construct($remove_keyboard = true) {
        $this->remove_keyboard = $remove_keyboard;
    }
}

class KeyboardButton {
    public $text;

    public function __construct($text) {
        $this->text = $text;
    }
}

class InputMedia {
    public $media;
    public $type;

    public function __construct($id, $type) {
        $this->media = $id;
        $this->type = $type;
    }
}

class Teacher {
    public $id;
    public $institute;
    public $faculty;
    public $department;
    public $name;

    public function __construct($id, $institute, $faculty, $department, $name) {
        $this->id = $id;
        $this->institute = $institute;
        $this->faculty = $faculty;
        $this->department = $department;
        $this->name = $name;
    }
}

class Group {
    public $id;
    public $type;
    public $faculty;
    public $year;
    public $name;

    public function __construct($id, $type, $faculty, $year, $name) {
        $this->id = $id;
        $this->type = $type;
        $this->faculty = $faculty;
        $this->year = $year;
        $this->name = $name;
    }
}

class SubObject {
    public function __construct($data) {
        foreach($data as $key => $value) {
            $this->$key = $value;
        }
    }
}
