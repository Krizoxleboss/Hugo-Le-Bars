<?php
date_default_timezone_set('Europe/Paris'); // Définir le fuseau horaire approprié

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du front end
    $city = $_POST["city"];

    // Votre clé API OpenWeatherMap
    $apiKey = 'dc220a0c718ddbeac6f9b73d81632082';

    // URL pour les données météorologiques actuelles
    $currentWeatherUrl = "https://api.openweathermap.org/data/2.5/weather?q=$city&appid=$apiKey&units=metric&lang=fr";

    // Récupérer les données météorologiques actuelles
    $currentData = file_get_contents($currentWeatherUrl);
    $currentData = json_decode($currentData, true); // Convertir la réponse JSON en tableau associatif

    // URL pour les prévisions météorologiques
    $forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?q=$city&appid=$apiKey&units=metric&lang=fr";

    // Récupérer les données de prévision
    $forecastData = file_get_contents($forecastUrl);
    $forecastData = json_decode($forecastData, true); // Convertir la réponse JSON en tableau associatif

    // Connexion à la base de données (à personnaliser avec vos informations)
    $host = 'localhost';
    $username = 'root';
    $password = 'root';
    $database = 'meteo_app';

    $conn = new mysqli($host, $username, $password, $database);

    // Vérifier la connexion
    if ($conn->connect_error) {
        die("Échec de la connexion à la base de données: " . $conn->connect_error);
    }

    // Insertion des données actuelles dans la table current_weather
    $sqlInsertCurrent = "INSERT INTO current_weather (city, temperature, feels_like, conditions, wind_speed, wind_direction, humidity, pressure, visibility, sunrise_time, sunset_time)
                        VALUES (
                            '$city',
                            '{$currentData['main']['temp']}',
                            '{$currentData['main']['feels_like']}',
                            '{$currentData['weather'][0]['description']}',
                            '{$currentData['wind']['speed']}',
                            '{$currentData['wind']['deg']}',
                            '{$currentData['main']['humidity']}',
                            '{$currentData['main']['pressure']}',
                            '{$currentData['visibility']}',
                            '08:35:18',  -- Remplacez ces valeurs par les valeurs réelles de sunrise et sunset
                            '16:50:02'
                        )";

    if ($conn->query($sqlInsertCurrent) === TRUE) {
        // Insertion réussie pour les données actuelles
    } else {
        echo "Erreur lors de l'insertion des données actuelles: " . $conn->error;
    }

    // Insertion des prévisions dans la table forecast
    foreach ($forecastData['list'] as $forecast) {
        $dateTime = date('Y-m-d H:i:s', $forecast['dt']);
        $sqlInsertForecast = "INSERT INTO forecast (city, date_time, temperature, feels_like, conditions, wind_speed, wind_direction, humidity, pressure, visibility)
                              VALUES (
                                  '$city',
                                  '$dateTime',
                                  '{$forecast['main']['temp']}',
                                  '{$forecast['main']['feels_like']}',
                                  '{$forecast['weather'][0]['description']}',
                                  '{$forecast['wind']['speed']}',
                                  '{$forecast['wind']['deg']}',
                                  '{$forecast['main']['humidity']}',
                                  '{$forecast['main']['pressure']}',
                                  '{$forecast['visibility']}'
                              )";

        if ($conn->query($sqlInsertForecast) !== TRUE) {
            echo "Erreur lors de l'insertion des prévisions: " . $conn->error;
        }
    }

    // Fermer la connexion
    $conn->close();

    // Retourner les données au front end
    header('Content-Type: application/json');
    echo json_encode(['currentData' => $currentData, 'forecastData' => $forecastData]);
    exit;
}
?>
