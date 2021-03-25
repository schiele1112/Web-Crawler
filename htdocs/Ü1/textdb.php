<?php
    $con = odbc_connect("testdb", "","");
    echo odbc_errormsg($con);

    odbc_exec($con, "INSERT INTO user.txt (ID, FIRSTNAME) VALUES ('100', 'Hannes');");
    echo odbc_errormsg($con);

    $result = odbc_exec($con,"SELECT * FROM user.txt");

    echo odbc_errormsg($con);
    odbc_result_all($result, "cellspacing = 5 border = \"1\"");
    // ...oder wie bei JDBC - eher konventionell...
    print "<br>";
    while (odbc_fetch_row($result) == true) {
        print odbc_result($result, "id");
        print " ";
        print odbc_result($result, "firstname");
        print " ";
        print odbc_result($result, "lastname");
        print "<br>";
    };
 odbc_close_all();
?>
