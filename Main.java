import org.apache.pdfbox.pdmodel.PDDocument;
import technology.tabula.*;
import technology.tabula.extractors.SpreadsheetExtractionAlgorithm;

import java.io.BufferedInputStream;
import java.io.File;
import java.io.IOException;
import java.net.URL;
import java.sql.*;
import java.text.DateFormat;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.time.Year;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.stream.Collectors;


import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

public class Main {

    public static String user = "admin_default";
    public static String password = "3gpMlUb4xS";
    public static String database = "admin_default";
    public static String url = "jdbc:mysql://185.221.152.90/" + database + "?useUnicode=true&serverTimezone=UTC&characterEncoding=UTF8";

    public static Connection connection;
    public static Statement stmt;

    public static void main(String[] args) throws IOException, SQLException, ParseException {
        String query = "SELECT * FROM `group`";
//        String query = "SELECT * FROM `group` WHERE `id`=14680";
        ArrayList<HashMap<String, String>> groups = new ArrayList<>();

        /*Connection */
        connection = DriverManager.getConnection(url, user, password);
        /*Statement */
        stmt = connection.createStatement();

        ResultSet rs = stmt.executeQuery(query);

        while (rs.next()) {
            HashMap<String, String> group = new HashMap<>();
            group.put("id", rs.getString(1));
            group.put("schedule", rs.getString(6));
            group.put("next_schedule", rs.getString(7));
            group.put("session", rs.getString(9));
            groups.add(group);
        }

        int current = 1;
        int groups_count = groups.toArray().length;
        for (HashMap<String, String> group : groups) {
            System.out.println(current++ + "/" + groups_count + ") Group_id = " + group.get("id"));
            if (!group.get("schedule").equals("")) {
                parse_url(group.get("schedule"), "0");
            } else {
                System.out.println(stmt.executeUpdate("DELETE FROM `schedule` WHERE `group_id` = " + group.get("id")) + " row(s) deleted by current schedule");
            }
            if (!group.get("next_schedule").equals("")) {
                parse_url(group.get("next_schedule"), "1");
            } else {
                System.out.println(stmt.executeUpdate("DELETE FROM `schedule` WHERE `group_id` = " + group.get("id") + " AND `schedule_number`=1") + " row(s) deleted by next schedule");
            }
            /*if (!group.get("session").equals("")) {
                parse_url(group.get("session"), "-1");
            } else {
                System.out.println(stmt.executeUpdate("DELETE FROM `schedule` WHERE `group_id` = " + group.get("id") + " AND `schedule_number`=-1") + " row(s) deleted by session schedule");
            }*/
        }
        connection.close();
    }


    public static void parse_url(String url, String schedule_number) throws IOException, ParseException {
//        System.out.println(url);
//        File schedule_pdf = new File(url);
        BufferedInputStream schedule_pdf = new BufferedInputStream(new URL(url).openStream());

        PDDocument pd = PDDocument.load(schedule_pdf);

        int total_pages = pd.getNumberOfPages();

        ObjectExtractor oe = new ObjectExtractor(pd);
        SpreadsheetExtractionAlgorithm sea = new SpreadsheetExtractionAlgorithm();

        Pattern pattern_place = Pattern.compile("\\s+\\d{1,2}\\s*-\\s*.+");
        Pattern pattern_subgroup = Pattern.compile("\\d+ подгруппа");

        String group_name = "";

        ArrayList<HashMap<String, String>> lessons = new ArrayList<>();
        Map<Float, StringBuilder> other_text = new HashMap<>();//for determine dates

        int number_of_lessons_per_day = 6;
        int min_number_of_lessons_per_day = 7;

        for (int page_num = 1; page_num <= total_pages; page_num++) {
            Page page = oe.extract(page_num);
            List<Table> tables = sea.extract(page);

            for (Table table : tables) {
                int row_count = table.getRowCount();
                int column_count = table.getColCount();
                if ((row_count > 0) && (column_count > 0)) {
                    double time_column = Math.round(table.getCell(0, (table.getCell(0, 2).x == 0 ? 0 : 1)).x);
                    double lesson_column = Math.round(table.getCell(0, (table.getCell(0, 2).x == 0 ? 1 : 2)).x);
                    int row_num_start = 0;
                    var day_cell = table.getCell(0, 0);
                    if (day_cell instanceof Cell) {
                        if (page_num == 1) {
                            row_num_start = 1;
                            var group_name_cell = table.getCell(0, 2);
                            if ((group_name_cell instanceof Cell) && (group_name_cell.x != 0)) {
                                group_name = getCellsText(page, (Cell) group_name_cell, 0);
                            }
                        }
                    }
                    String time = "18:55-20:25";//нашел групу, у которой все очень плохо, даже время наполовину обрезано https://www.vyatsu.ru/reports/schedule/Group/14646_1_07122020_20122020.pdf
                    StringBuilder lesson_string = new StringBuilder();
                    for (int row_num = row_num_start; row_num < row_count; row_num++) {
                        HashMap<String, String> lesson = new HashMap<>();
                        for (int column_num = 0; column_num < column_count; column_num++) {
                            var cell = table.getCell(row_num, column_num);
                            if (cell instanceof Cell) {
                                if (Math.round(cell.x) == time_column) {
                                    String old_time = time;
                                    time = getCellsText(page, (Cell) cell, 0);
                                    number_of_lessons_per_day++;
                                    if (time.startsWith("08:20")) {
                                        if (number_of_lessons_per_day < min_number_of_lessons_per_day) {
                                            System.out.println("Number of lessons per day(" + number_of_lessons_per_day + ") less then min(" + min_number_of_lessons_per_day + ")");
                                            return;
                                        }
                                        number_of_lessons_per_day = 0;
                                    }
                                } else if (Math.round(cell.x) == lesson_column) {//lesson's title
                                    if (time.equals("")) return;
                                    if (lesson_string.toString().equals("")) {
                                        lesson_string = new StringBuilder(getCellsText(page, (Cell) cell, 0));
                                    } else {
                                        lesson_string.append(getCellsText(page, (Cell) cell, 0));
                                    }
                                    if ((lesson_string.length() > 0) && (lesson_string.substring(lesson_string.length()-1).equals(" "))) {
                                        continue;
                                    }
                                    lesson.put("lesson", lesson_string.toString().replace(group_name + ", ", "").replaceAll("[`*_]", "").trim());
                                    lesson_string = new StringBuilder();
                                    Matcher matcher = pattern_subgroup.matcher(lesson.get("lesson"));
                                    if (matcher.find()) {
                                        lesson.put("lesson", lesson.get("lesson").replace(matcher.group(), Integer.parseInt(matcher.group().replaceAll("[^0-9]", "")) + " подгруппа"));
                                    }
                                    matcher = pattern_place.matcher(lesson.get("lesson"));
                                    if (matcher.find()) {
                                        lesson.put("lesson", lesson.get("lesson").replace(matcher.group(), ""));
                                        lesson.put("audience", matcher.group().replaceAll("\\s+", ""));
                                        lesson.put("link", "");
                                    } else {
                                        lesson.put("audience", "");
                                        if ((!lesson.get("lesson").equals("")) && (!lesson.get("lesson").contains("дистанционно")) && (!lesson.get("lesson").contains(". .")) && (!lesson.get("lesson").contains("ная практика"))) {
                                            lesson.put("link", "https://teams.microsoft.com/");
                                        } else {
                                            lesson.put("link", "");
                                        }

                                    }
                                    lesson.put("time_start", time.split("-")[0]);
                                    lesson.put("time_finish", time.split("-")[1]);
//                                    lesson.put("page_num", Integer.toString(page_num));
                                    lesson.put("schedule_number", schedule_number);
                                }
                            }
                        }
                        if (lesson.size() == 6) {
                            lessons.add(lesson);
                        }
                    }
                }
            }
            for (TextElement chr : page.getText(page.getTextBounds())) {
                if (other_text.get(chr.getDirection()) == null) {
                    other_text.put(chr.getDirection(), new StringBuilder());
                }
                other_text.put(chr.getDirection(), other_text.get(chr.getDirection()).append(chr.getText()));
            }
        }
        if (++number_of_lessons_per_day < min_number_of_lessons_per_day) {
            System.out.println("Number of lessons per day(" + number_of_lessons_per_day + ") less then min(" + min_number_of_lessons_per_day + ")");
            return;
        }
        Pattern pattern_date = Pattern.compile("\\d{2}\\.\\d{2}\\.\\d{2}");
//        Pattern pattern_date = Pattern.compile("\\d{2}\\.\\d{2}");
        List<String> dates = new ArrayList<>();
        for (Map.Entry<Float, StringBuilder> entry : other_text.entrySet()) {
            Matcher matcher = pattern_date.matcher(entry.getValue());
            while (matcher.find()) {
                String[] date = matcher.group().split("\\.");
                dates.add("20" + date[2] + "-" + date[1] + "-" + date[0]);
            }
        }
        dates = dates.stream().distinct().collect(Collectors.toList());
        dates.sort(new Comparator<>() {
            final DateFormat f = new SimpleDateFormat("yyyy-MM-dd");

            @Override
            public int compare(String o1, String o2) {
                try {
                    return f.parse(o1).compareTo(f.parse(o2));
                } catch (ParseException e) {
                    throw new IllegalArgumentException(e);
                }
            }
        });
        if (dates.size() % 7 != 0) {//Если количество дат не кратно 7, то берем отсчет от первого дня
            String[] schedule_dates = Pattern.compile("https://www.vyatsu.ru/reports/schedule/Group/\\d+_\\d+_").matcher(url).replaceAll("").replace(".pdf", "").split("_");
//            String[] schedule_dates = Pattern.compile("C:/Users/Admin/Downloads/\\d+_\\d+_").matcher(url).replaceAll("").replace(".pdf", "").split("_");
            dates = new ArrayList<>();
            dates.add(schedule_dates[0].substring(4) + "-" + schedule_dates[0].substring(2, 4) + "-" + schedule_dates[0].substring(0, 2));
            for (int day = 0; day < 13; day++) {
                SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd");
                Calendar c = Calendar.getInstance();
                c.setTime(sdf.parse(dates.get(day)));
                c.add(Calendar.DATE, 1);  // number of days to add
                dates.add(sdf.format(c.getTime()));
            }
        }

        Filling(Integer.parseInt(url.split("_")[0].split("/")[url.split("_")[0].split("/").length - 1]), dates, lessons);
    }

    public static String getCellsText(Page page, Cell cell, float direction) {
        StringBuilder text = new StringBuilder();
//        double y = 0;//for "\n"
        for (TextElement chr : page.getText(new Rectangle(cell.y, cell.x, cell.width, cell.height))) {
            if (chr.getDirection() == direction) {
//                if (y != chr.y) {//for "\n"
//                    text.append("\n");
//                }
                text.append(chr.getText());
//                y = chr.y;//for "\n"
            }
        }

//        return text.substring(y != 0 ? 1 : 0).replaceAll("\\s*\\n", "\n");//for "\n"
        return text.toString();
    }

    public static void Filling(int group_id, List<String> dates, ArrayList<HashMap<String, String>> lessons) {
        if ((lessons.size() == 0) || (dates.size() == 0)) {
            System.out.println("Groups list empty, it's bad, fix it!!");
            return;
        }

        System.out.println("Count lessons: " + lessons.size());
//        System.out.println(lessons);
//        System.out.println(dates);

        try {
//            Connection connection = DriverManager.getConnection(url, user, password);
//            Statement stmt = connection.createStatement();

            int date_number = 0;
            int lessons_per_day = 0;
            String current_time = "";
            String last_lesson_time = "";
            int sort = 0;
            String query;
            int upd = 0;
            int del = 0;
            for (HashMap<String, String> lesson : lessons) {
                if (!current_time.equals(lesson.get("time_start"))) {
                    if (sort != 0) {
                        query = "DELETE FROM `schedule` WHERE `group_id`=" + group_id + " AND `date`='" + dates.get(date_number) + "' AND `time_start`='" + current_time + ":00' AND `sort`>=" + sort + ";";
                        int deleted = stmt.executeUpdate(query);
                        del += deleted;
                    }
                    sort = 0;
                    current_time = lesson.get("time_start");
                }
                if ((lessons_per_day++ > 5) && (lesson.get("time_start").equals("08:20")) && (last_lesson_time.equals("18:55"))) {
                    date_number++;
                    lessons_per_day = 0;
                }
                query = "INSERT INTO `schedule` (`group_id`, `date`, `time_start`, `time_finish`, `lesson`, `audience`, `link`, `sort`, `schedule_number`) VALUES (" + group_id + ", '" + dates.get(date_number) + "', '" + lesson.get("time_start") + "', '" + lesson.get("time_finish") + "', '" + lesson.get("lesson") + "', '" + lesson.get("audience") + "', '" + lesson.get("link") + "', " + sort++ + ", " + lesson.get("schedule_number") + ") ON DUPLICATE KEY UPDATE `audience`='" + lesson.get("audience") + "', `link`='" + lesson.get("link") + "', `lesson`='" + lesson.get("lesson") + "', `schedule_number`=" + lesson.get("schedule_number") + ";";
                upd += stmt.executeUpdate(query);
                last_lesson_time = lesson.get("time_start");
            }
            System.out.println(upd + " record(s) created/updated");
            System.out.println(del + " record(s) deleted");

//            connection.close();
        } catch (SQLException | IndexOutOfBoundsException e) {
            e.printStackTrace();
        }
    }
}


