<?php
// Connexion à la base de données
$conn = new mysqli('marksports.eu.mysql', 'marksports_eu', 'Marksports12', 'marksports_eu');

// Vérifier la connexion
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupérer le club depuis le formulaire
$club = isset($_POST['club']) ? $conn->real_escape_string($_POST['club']) : NULL;
if (empty($club)) {
    die("Erreur : Le club doit être spécifié.");
}

// Démarrer une transaction
$conn->begin_transaction();

try {
    // Récupérer le dernier numéro de commande pour ce club en verrouillant la ligne
    $result = $conn->query("SELECT MAX(order_number) AS max_order_number FROM orders WHERE club = '$club' FOR UPDATE");
    if ($result) {
        $row = $result->fetch_assoc();
        $nextOrderNumber = $row['max_order_number'] ? $row['max_order_number'] + 1 : 1;
    } else {
        $nextOrderNumber = 1; // Par défaut, commence à 1 si aucun numéro de commande n'existe pour ce club
    }

    // Récupérer les données du formulaire en toute sécurité
    $name = isset($_POST['name']) ? $conn->real_escape_string($_POST['name']) : NULL;
    $firstname = isset($_POST['firstname']) ? $conn->real_escape_string($_POST['firstname']) : NULL;
    $category = isset($_POST['category']) ? $conn->real_escape_string($_POST['category']) : NULL;
    $phone = isset($_POST['phone']) ? $conn->real_escape_string($_POST['phone']) : NULL;
    $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : NULL;
    $jacket_size = isset($_POST['jacket_size']) ? $conn->real_escape_string($_POST['jacket_size']) : NULL;
    $pants_size = isset($_POST['pants_size']) ? $conn->real_escape_string($_POST['pants_size']) : NULL;
    $kit_size = isset($_POST['kit_size']) ? $conn->real_escape_string($_POST['kit_size']) : 'Not Provided'; // Valeur par défaut
    $under_shirt_size = isset($_POST['under_shirt_size']) ? $conn->real_escape_string($_POST['under_shirt_size']) : NULL;
    $option_kway = isset($_POST['option_kway']) ? $conn->real_escape_string($_POST['option_kway']) : NULL;
    $bas_size = isset($_POST['bas_size']) ? $conn->real_escape_string($_POST['bas_size']) : NULL;
    $initials_requested = isset($_POST['initials_requested']) ? (int)$_POST['initials_requested'] : 0;
    $initials = isset($_POST['initials']) ? $conn->real_escape_string($_POST['initials']) : NULL;
    $role = isset($_POST['role']) ? $conn->real_escape_string($_POST['role']) : 'default_role'; // Valeur par défaut si non définie
    $jersey_size = isset($_POST['jersey_size']) ? $conn->real_escape_string($_POST['jersey_size']) : null;
    $short_size = isset($_POST['short_size']) ? $conn->real_escape_string($_POST['short_size']) : null;
    $polo_size = isset($_POST['polo_size']) ? $conn->real_escape_string($_POST['polo_size']) : null;

    // Vérification des champs obligatoires
    if (empty($name) || empty($firstname) || empty($category) || empty($phone) || empty($email)) {
        die("Erreur : Tous les champs obligatoires doivent être remplis.");
    }

    // Mise à jour de la requête SQL pour inclure 'role'
    $sql = "INSERT INTO orders (order_number, name, firstname, category, phone, email, jacket_size, pants_size, kit_size, bas_size, under_shirt_size, option_kway, initials_requested, initials, club, role, jersey_size, short_size, polo_size) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Préparer et lier les paramètres
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Lier les paramètres en tenant compte de la possibilité de NULL
        $stmt->bind_param(
        "sssssssssssssssssss", // 16 paramètres à lier (ajout d'un 's' pour jersey_size)
        $nextOrderNumber,     // order_number
        $name,             // name
        $firstname,        // firstname
        $category,         // category
        $phone,            // phone
        $email,            // email
        $jacket_size,      // jacket_size
        $pants_size,       // pants_size
        $kit_size,   
        $bas_size,         // socks_size
        $under_shirt_size, // under_shirt_size
        $option_kway,      // option_kway
        $initials_requested, // initials_requested
        $initials,         // initials
        $club,             // club
        $role,             // role
        $jersey_size,       // jersey_size
        $short_size,
        $polo_size
        );

        // Exécution de la requête
        if ($stmt->execute()) {
            // Commit the transaction
            $conn->commit();
            echo "Nouvelle commande créée avec succès. Numéro de commande : $nextOrderNumber";
        } else {
            // Annuler la transaction en cas d'erreur
            $conn->rollback();
            echo "Erreur lors de l'ajout de la commande : " . $stmt->error;
        }

        // Fermer le statement
        $stmt->close();
    } else {
        // Annuler la transaction en cas d'erreur
        $conn->rollback();
        echo "Erreur lors de la préparation de la requête : " . $conn->error;
    }

} catch (Exception $e) {
    // En cas d'exception, annuler la transaction
    $conn->rollback();
    echo "Erreur : " . $e->getMessage();
}

// Fermer la connexion
$conn->close();
?>
