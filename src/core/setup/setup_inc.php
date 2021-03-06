<?php
/*
SETTING UP A NEW TABLE:
1. [OPTIONAL] Put the name of your table as a new variable in connection_vars.php.
2. Append your information to the specs or add them here. TODO: Create specs.
3. Put your CREATE TABLE statement in here, similar to already existing code.
4. increment the version numbers in version_number.php.
5. for the new version number, append another if statement into the doUpdate.php, so your changes will be carried over to all existing databases on different systems.
6. relog inside program with a core admin account. you should see an update.

MAKING CHANGES TO EXISTING TABLE:
1. make your changes in here
2. write an ALTER TABLE statement inside doUpdate.php, with the new version number
3. increment the numbers in version_number.php
4. relog inisde program with an account with core priviliges.

Please test the setup after every change.
*/
function create_tables($conn) {
    $sql = "CREATE TABLE UserData (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		email VARCHAR(50) UNIQUE NOT NULL,
		psw VARCHAR(60) NOT NULL,
        firstname VARCHAR(30),
        lastname VARCHAR(30) NOT NULL,
        coreTime TIME DEFAULT '08:00:00',
        terminalPin INT(8) DEFAULT 4321,
        lastPswChange DATETIME DEFAULT CURRENT_TIMESTAMP,
        beginningDate DATETIME DEFAULT CURRENT_TIMESTAMP,
        exitDate DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        gender ENUM('female', 'male'),
        preferredLang ENUM('ENG', 'GER', 'FRA', 'ITA') DEFAULT 'GER',
        kmMoney DECIMAL(4,2) DEFAULT 0.42,
        emUndo DATETIME DEFAULT CURRENT_TIMESTAMP,
        color VARCHAR(10) DEFAULT 'dark',
        real_email VARCHAR(50),
        forcedPwdChange TINYINT(1) NOT NULL DEFAULT 0,
        erpOption VARCHAR(10) DEFAULT 'TRUE',
        strikeCount INT(3) DEFAULT 0,
        birthday DATE,
        companyID INT(6) UNSIGNED,
		supervisor INT(6) DEFAULT NULL,
        lastLogin DATETIME DEFAULT NULL,
		displayBirthday ENUM('TRUE', 'FALSE') NOT NULL DEFAULT 'FALSE',
		canLogin ENUM('TRUE', 'FALSE') NOT NULL DEFAULT 'TRUE',
        inherit_team_permissions ENUM('TRUE', 'FALSE') NOT NULL DEFAULT 'TRUE'
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE logs (
        indexIM INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userID INT(6) UNSIGNED,
        time DATETIME NOT NULL,
        timeEnd DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        timeToUTC INT(2) DEFAULT '2',
        status INT(3) NOT NULL DEFAULT 0,
        emoji INT(2) NOT NULL DEFAULT 0,
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE holidays(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        begin DATETIME NOT NULL,
        end DATETIME,
        name VARCHAR(60) NOT NULL
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE companyData (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(60) NOT NULL,
        cmpDescription VARCHAR(50),
        companyType ENUM('GmbH', 'AG', 'OG', 'KG', 'EU', '-') DEFAULT '-',
        logo MEDIUMBLOB,
        address VARCHAR(100),
        companyPostal VARCHAR(20),
        companyCity VARCHAR(60),
        phone VARCHAR(100),
        mail VARCHAR(100),
        homepage VARCHAR(100),
        erpText TEXT,
        detailLeft VARCHAR(120),
        detailMiddle VARCHAR(120),
        detailRight VARCHAR(120),
        uid VARCHAR(20),
        istVersteuerer ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
		companyRegister VARCHAR(80),
		companyCommercialCourt VARCHAR(80),
		companyWKOLink VARCHAR(150),
		fax VARCHAR(60),
		emailSignature TEXT,
		ecoYear DATE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE clientData(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(60) NOT NULL,
        companyID INT(6) UNSIGNED,
        clientNumber VARCHAR(15),
		status VARCHAR(25),
        isSupplier VARCHAR(10) DEFAULT 'FALSE',
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE projectData(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        clientID INT(6) UNSIGNED,
        name VARCHAR(60) NOT NULL,
        hours DECIMAL(5,2),
        status VARCHAR(30),
        hourlyPrice DECIMAL(6,2) DEFAULT 0,
        field_1 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        field_2 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        field_3 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        creator INT(6),
        FOREIGN KEY (clientID) REFERENCES clientData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE projectBookingData (
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        start DATETIME NOT NULL,
        end DATETIME NOT NULL,
        chargedTimeStart DATETIME DEFAULT '0000-00-00 00:00:00',
        chargedTimeEnd DATETIME DEFAULT '0000-00-00 00:00:00',
        projectID INT(6) UNSIGNED,
        timestampID INT(10) UNSIGNED NOT NULL,
        infoText VARCHAR(500),
        internInfo VARCHAR(500),
        booked ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        bookingType ENUM('project', 'break', 'drive', 'mixed'),
        mixedStatus INT(3) DEFAULT -1,
        extra_1 VARCHAR(200) NULL DEFAULT NULL,
        extra_2 VARCHAR(200) NULL DEFAULT NULL,
        extra_3 VARCHAR(200) NULL DEFAULT NULL,
        exp_info TEXT,
        exp_price DECIMAL(10,2),
        exp_unit DECIMAL(10,2),
        dynamicID VARCHAR(100),
        FOREIGN KEY (projectID) REFERENCES projectData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (timestampID) REFERENCES logs(indexIM)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        UNIQUE KEY double_submit (timestampID, start, end)
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE relationship_company_client (
        companyID INT(6) UNSIGNED,
        userID INT(6) UNSIGNED,
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE companyDefaultProjects (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(60) NOT NULL,
        companyID INT(6) UNSIGNED,
        hours INT(3),
        status VARCHAR(30),
        hourlyPrice DECIMAL(6,2) DEFAULT 0,
        field_1 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        field_2 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        field_3 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        UNIQUE KEY name_company (name, companyID)
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE configurationData(
        version INT(5),
        bookingTimeBuffer INT(3) DEFAULT '5',
        cooldownTimer INT(3) DEFAULT '2',
        enableReadyCheck ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
        enableReg ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
        activeEncryption ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' NOT NULL,
        firstTimeWizard ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' NOT NULL,
		sessionTime DECIMAL(4,2) DEFAULT 4.0 NOT NULL
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    //status: 0 = open, 1 = declined, 2 = accepted
    $sql = "CREATE TABLE userRequestsData(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userID INT(6) UNSIGNED,
        fromDate DATETIME NOT NULL,
        toDate DATETIME,
        status ENUM('0', '1', '2') DEFAULT '0',
        requestType VARCHAR(3) DEFAULT 'vac' NOT NULL,
        requestText VARCHAR(150),
        answerText VARCHAR(150),
        requestID INT(10) DEFAULT 0,
        timeToUTC INT(2) DEFAULT 0,
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE gitHubConfigTab(
        sslVerify ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE travelCountryData(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        identifier VARCHAR(10) NOT NULL,
        countryName VARCHAR(50),
        dayPay DECIMAL(6,2) DEFAULT 0,
        nightPay DECIMAL(6,2) DEFAULT 0
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE travelBookings(
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userID INT(6) UNSIGNED,
        countryID INT(6) UNSIGNED,
        travelDayStart DATETIME NOT NULL,
        travelDayEnd DATETIME NOT NULL,
        kmStart INT(8),
        kmEnd INT(8),
        infoText VARCHAR(500),
        hotelCosts DECIMAL(8,2) DEFAULT 0,
        hosting10 DECIMAL(6,2) DEFAULT 0,
        hosting20 DECIMAL(6,2) DEFAULT 0,
        expenses DECIMAL(8,2) DEFAULT 0,
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (countryID) REFERENCES travelCountryData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    //deactivated tables
    $sql = "CREATE TABLE DeactivatedUsers (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        firstname VARCHAR(30),
        lastname VARCHAR(30) NOT NULL,
        psw VARCHAR(60) NOT NULL,
        coreTime TIME DEFAULT '08:00:00',
        terminalPin INT(8) DEFAULT 4321,
        lastPswChange DATETIME DEFAULT CURRENT_TIMESTAMP,
        beginningDate DATETIME DEFAULT CURRENT_TIMESTAMP,
        exitDate DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        email VARCHAR(50) UNIQUE NOT NULL,
        sid VARCHAR(50),
        gender ENUM('female', 'male'),
        preferredLang ENUM('ENG', 'GER', 'FRA', 'ITA') DEFAULT 'GER',
        kmMoney DECIMAL(4,2) DEFAULT 0.42,
        emUndo DATETIME DEFAULT CURRENT_TIMESTAMP,
        real_email VARCHAR(50)
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE DeactivatedUserLogData (
        indexIM INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        time DATETIME NOT NULL,
        timeEnd DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        status INT(3),
        timeToUTC INT(2) DEFAULT '2',
        userID INT(6) UNSIGNED,
        FOREIGN KEY (userID) REFERENCES DeactivatedUsers(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE DeactivatedUserData(
        userID INT(6) UNSIGNED,
        startDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        endDate DATETIME DEFAULT NULL,
        mon DECIMAL(4,2) DEFAULT 8.5,
        tue DECIMAL(4,2) DEFAULT 8.5,
        wed DECIMAL(4,2) DEFAULT 8.5,
        thu DECIMAL(4,2) DEFAULT 8.5,
        fri DECIMAL(4,2) DEFAULT 4.5,
        sat DECIMAL(4,2) DEFAULT 0,
        sun DECIMAL(4,2) DEFAULT 0,
        vacPerYear INT(2) DEFAULT 25,
        overTimeLump DECIMAL(5,2) DEFAULT 0.0,
        pauseAfterHours DECIMAL(4,2) DEFAULT 6,
        hoursOfRest DECIMAL(4,2) DEFAULT 0.5,
        FOREIGN KEY (userID) REFERENCES DeactivatedUsers(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE DeactivatedUserProjectData (
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        start DATETIME NOT NULL,
        end DATETIME NOT NULL,
        chargedTimeStart DATETIME DEFAULT '0000-00-00 00:00:00',
        chargedTimeEnd DATETIME DEFAULT '0000-00-00 00:00:00',
        projectID INT(6) UNSIGNED,
        timestampID INT(10) UNSIGNED NOT NULL,
        infoText VARCHAR(500),
        internInfo VARCHAR(500),
        booked ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        bookingType ENUM('project', 'break', 'drive', 'mixed'),
        mixedStatus INT(3) DEFAULT -1,
        extra_1 VARCHAR(200) NULL DEFAULT NULL,
        extra_2 VARCHAR(200) NULL DEFAULT NULL,
        extra_3 VARCHAR(200) NULL DEFAULT NULL,
        exp_info TEXT,
        exp_price DECIMAL(10,2),
        exp_unit DECIMAL(10,2),
        FOREIGN KEY (projectID) REFERENCES projectData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (timestampID) REFERENCES DeactivatedUserLogData(indexIM)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE DeactivatedUserTravelData(
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userID INT(6) UNSIGNED,
        countryID INT(6) UNSIGNED,
        travelDayStart DATETIME NOT NULL,
        travelDayEnd DATETIME NOT NULL,
        kmStart INT(8),
        kmEnd INT(8),
        infoText VARCHAR(500),
        hotelCosts DECIMAL(8,2) DEFAULT 0,
        hosting10 DECIMAL(6,2) DEFAULT 0,
        hosting20 DECIMAL(6,2) DEFAULT 0,
        expenses DECIMAL(8,2) DEFAULT 0,
        FOREIGN KEY (userID) REFERENCES DeactivatedUsers(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (countryID) REFERENCES travelCountryData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE clientInfoData(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		clientID INT(6) UNSIGNED,
        contactType ENUM('person', 'company'),
        gender ENUM('female', 'male'),
        title VARCHAR(30),
        name VARCHAR(45),
        firstname VARCHAR(45),
        nameAddition VARCHAR(45),
        address_Street VARCHAR(100),
        address_Country INT(6) UNSIGNED,
        address_Country_Postal VARCHAR(20),
        address_Country_City VARCHAR(50),
        address_Addition VARCHAR(150),
        phone VARCHAR(20),
        fax_number VARCHAR(30),
        debitNumber INT(10),
        datev INT(10),
        accountName VARCHAR(100),
        taxnumber VARCHAR(50),
        vatnumber VARCHAR(50),
        taxArea VARCHAR(50),
        customerGroup VARCHAR(50),
        representative VARCHAR(50),
        blockDelivery ENUM('true', 'false') DEFAULT 'false',
        paymentMethod VARCHAR(100),
        shipmentType VARCHAR(100),
        creditLimit DECIMAL(10,2),
        eBill ENUM('true', 'false') DEFAULT 'false',
        lastFaktura DATETIME,
        warningEnabled ENUM('true', 'false') DEFAULT 'true',
        karenztage INT(4),
        lastWarning DATETIME,
        warning1 DECIMAL(10,2),
        warning2 DECIMAL(10,2),
        warning3 DECIMAL(10,2),
        calculateInterest ENUM('true', 'false'),
		billingMailAddress VARCHAR(100),
		homepage VARCHAR(100),
		mail VARCHAR(100),
		billDelivery VARCHAR(60),
        FOREIGN KEY (clientID) REFERENCES clientData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE clientInfoNotes(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        infoText VARCHAR(800),
        createDate DATETIME,
        parentID INT(6) UNSIGNED,
        FOREIGN KEY (parentID) REFERENCES clientInfoData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE clientInfoBank(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        bic VARCHAR(300),
        iban VARCHAR(400),
        bankName VARCHAR(400),
        parentID INT(6) UNSIGNED,
        FOREIGN KEY (parentID) REFERENCES clientInfoData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }


    $sql = "CREATE TABLE policyData (
        passwordLength INT(2) DEFAULT 6,
        complexity ENUM('0', '1', '2') DEFAULT '1',
        expiration ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
        expirationDuration INT(3) DEFAULT 3,
        expirationType ENUM('ALERT', 'FORCE') DEFAULT 'ALERT'
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE mailingOptions(
        host VARCHAR(50),
        username VARCHAR(50),
        password VARCHAR(50),
        port VARCHAR(50),
        smtpSecure ENUM('', 'tls', 'ssl') DEFAULT 'tls',
        sender VARCHAR(50) DEFAULT 'noreply@mail.com',
        enableEmailLog ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        senderName VARCHAR(50) DEFAULT NULL,
        feedbackRecipient VARCHAR(50) DEFAULT 'connect@eitea.at',
        isDefault TINYINT(1) NOT NULL DEFAULT 1
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE templateData(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        repeatCount VARCHAR(50),
        htmlCode TEXT,
        userIDs VARCHAR(200),
        type VARCHAR(10) NOT NULL DEFAULT 'report'
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE mailReports(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reportID INT(6) UNSIGNED,
        email VARCHAR(50) NOT NULL,
        FOREIGN KEY (reportID) REFERENCES templateData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE mailRecipients(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reportID INT(6) UNSIGNED,
        email VARCHAR(50) NOT NULL,
        name VARCHAR(50),
        FOREIGN KEY (reportID) REFERENCES templateData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    /*
    * cOnDate is Date this correction was created On
    * createdOn defines Month this corrections accounts for
    * (used names were swapped by mistake.)
    */
    $sql = "CREATE TABLE correctionData(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userID INT(6) UNSIGNED,
        hours DECIMAL(6,2),
        infoText VARCHAR(350),
        addOrSub ENUM('1', '-1') NOT NULL,
        cOnDate DATETIME DEFAULT CURRENT_TIMESTAMP,
        createdOn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        cType VARCHAR(10) NOT NULL DEFAULT 'log',
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE intervalData(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        startDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        endDate DATETIME DEFAULT NULL,
        mon DECIMAL(4,2) DEFAULT 8.5,
        tue DECIMAL(4,2) DEFAULT 8.5,
        wed DECIMAL(4,2) DEFAULT 8.5,
        thu DECIMAL(4,2) DEFAULT 8.5,
        fri DECIMAL(4,2) DEFAULT 4.5,
        sat DECIMAL(4,2) DEFAULT 0,
        sun DECIMAL(4,2) DEFAULT 0,
        vacPerYear INT(2) DEFAULT 25,
        overTimeLump DECIMAL(5,2) DEFAULT 0.0,
        pauseAfterHours DECIMAL(4,2) DEFAULT 6,
        hoursOfRest DECIMAL(4,2) DEFAULT 0.5,
        userID INT(6) UNSIGNED,
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE teamData (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(60),
        companyID INT(6) UNSIGNED,
        leader INT(6),
        leaderreplacement INT(6),
        isDepartment ENUM('TRUE', 'FALSE') NOT NULL DEFAULT 'FALSE',
		email VARCHAR(100),
		emailName VARCHAR(50),
		emailSignature TEXT,
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE relationship_team_user (
        teamID INT(6) UNSIGNED,
        userID INT(6) UNSIGNED,
        skill INT(3) DEFAULT 0 NOT NULL,
        FOREIGN KEY (teamID) REFERENCES teamData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE additionalFields (
        id INT(6) UNSIGNED PRIMARY KEY,
        companyID INT(6) UNSIGNED,
        name VARCHAR(25) NOT NULL,
        isActive ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        isRequired ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        isForAllProjects ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        description VARCHAR(50),
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE taskData(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        repeatPattern ENUM('-1', '0', '1', '2', '3', '4','5') DEFAULT '-1',
        runtime DATETIME DEFAULT CURRENT_TIMESTAMP,
        description VARCHAR(200),
        lastRuntime DATETIME DEFAULT CURRENT_TIMESTAMP,
        callee VARCHAR(50) NOT NULL
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE proposals(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        clientID INT(6) UNSIGNED,
        curDate DATETIME DEFAULT CURRENT_TIMESTAMP,
        deliveryDate DATETIME,
        daysNetto INT(4),
        skonto1 DECIMAL(8,2),
        skonto2 DECIMAL(8,2),
        skonto1Days INT(4),
        skonto2Days INT(4),
        paymentMethod VARCHAR(100),
        shipmentType VARCHAR(100),
        representative VARCHAR(50),
        porto DECIMAL(8,2),
        portoRate INT(3),
        header VARCHAR(400),
        referenceNumrow VARCHAR(10),
		foreignOpt INT(2) NOT NULL DEFAULT 0,
        FOREIGN KEY (clientID) REFERENCES clientData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE processHistory(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_number VARCHAR(12) NOT NULL,
        status INT(2),
        processID INT(6) UNSIGNED,
        FOREIGN KEY (processID) REFERENCES proposals(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE products(
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        taxID INT(4),
        historyID INT(6) UNSIGNED,
        position INT(4),
        name VARCHAR(255) NOT NULL,
        description VARCHAR(400),
        price DECIMAL(10,2),
        unit VARCHAR(20),
        quantity DECIMAL(8,2),
        purchase DECIMAL(10,2),
        cash ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        origin VARCHAR(16),
        FOREIGN KEY (historyID) REFERENCES processHistory(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    /* IMPORTANT:
    Tax IDs must always correspond to their current positon in place.
    Taxes may only be altered under supervision of an accountant
    */
    $sql = "CREATE TABLE taxRates (
        id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        description VARCHAR(100),
        percentage INT(3),
        code INT(2),
        account2 INT(4),
        account3 INT(4)
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE mixedInfoData(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        timestampID INT(10) UNSIGNED,
        status INT(3),
        timeStart DATETIME,
        timeEnd DATETIME,
        isFillable ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
        FOREIGN KEY (timestampID) REFERENCES logs(indexIM)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE articles (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        companyID INT(6),
        taxID INT(4) UNSIGNED,
        name VARCHAR(255),
        description VARCHAR(1200),
        price DECIMAL(10,2),
        unit VARCHAR(20),
        cash ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        purchase DECIMAL(10,2)
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE units (
        id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(20) NOT NULL,
        unit VARCHAR(10) NOT NULL
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE paymentMethods (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        daysNetto INT(4),
        skonto1 DECIMAL(6,2),
        skonto2 DECIMAL(6,2),
        skonto1Days INT(4),
        skonto2Days INT(4)
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE representatives (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50)
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE shippingMethods (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100)
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE erp_settings(
		companyID INT(6) UNSIGNED,
		erp_ang INT(5) DEFAULT 1,
		erp_aub INT(5) DEFAULT 1,
		erp_re INT(5) DEFAULT 1,
		erp_lfs INT(5) DEFAULT 1,
		erp_gut INT(5) DEFAULT 1,
		erp_stn INT(5) DEFAULT 1,
		yourSign VARCHAR(30),
		yourOrder VARCHAR(30),
		ourSign VARCHAR(30),
		ourMessage VARCHAR(30),
		clientNum VARCHAR(12),
		clientStep INT(2),
		supplierNum VARCHAR(12),
		supplierStep INT(2),
		euService VARCHAR(150),
		euDelivery VARCHAR(150),
		FOREIGN KEY (companyID) REFERENCES companyData(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE socialprofile(
        userID INT(6) UNSIGNED,
        isAvailable ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
        status varchar(150) DEFAULT '-',
        picture MEDIUMBLOB,
        new_message_email ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        new_message_notification ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
		emailSignature TEXT,
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    if (!$conn->query("CREATE TABLE resticconfiguration(
        path VARCHAR(255),
        password VARCHAR(255),
        awskey VARCHAR(255),
        awssecret VARCHAR(255),
        location VARCHAR(255)
    )")){
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE identification(
        id VARCHAR(60) PRIMARY KEY
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE accounts (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        companyID INT(6) UNSIGNED,
        manualBooking VARCHAR(10) DEFAULT 'FALSE',
        num INT(4) UNSIGNED,
        name VARCHAR(50),
        type ENUM('1', '2', '3', '4') DEFAULT '1',
        options VARCHAR(10) DEFAULT 'STAT',
        UNIQUE KEY (companyID, num),
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE account_journal(
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userID INT(6),
        taxID INT(4) UNSIGNED,
        docNum INT(6),
        payDate DATETIME,
        inDate DATETIME,
        account INT(6) UNSIGNED,
        offAccount INT(6) UNSIGNED,
        info VARCHAR(70),
        should DECIMAL(18,2),
        have DECIMAL(18,2),
		status VARCHAR(10) DEFAULT 'account' NOT NULL,
        FOREIGN KEY (account) REFERENCES accounts(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (offAccount) REFERENCES accounts(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        UNIQUE KEY double_submit (userID, inDate)
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE account_balance(
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        journalID INT(10) UNSIGNED,
        accountID INT(4) UNSIGNED,
        should DECIMAL(18,2),
        have DECIMAL(18,2),
        FOREIGN KEY (accountID) REFERENCES accounts(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (journalID) REFERENCES account_journal(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE projectBookingData_audit(
        id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        changedat DATETIME,
        bookingID INT(6) UNSIGNED,
        statement VARCHAR(100)
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TRIGGER projectBookingData_update_trigger
    BEFORE UPDATE ON projectBookingData
    FOR EACH ROW
    BEGIN
    SELECT COUNT(*) INTO @cnt FROM projectBookingData_audit;
    IF @cnt >= 150 THEN
    DELETE FROM projectBookingData_audit ORDER BY id LIMIT 1;
    END IF;
    INSERT INTO projectBookingData_audit
    SET changedat = UTC_TIMESTAMP, bookingID = OLD.id, statement = CONCAT('UPDATE ', OLD.id);
    END";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TRIGGER projectBookingData_delete_trigger
    BEFORE DELETE ON projectBookingData
    FOR EACH ROW
    BEGIN
    SELECT COUNT(*) INTO @cnt FROM projectBookingData_audit;
    IF @cnt >= 150 THEN
    DELETE FROM projectBookingData_audit ORDER BY id LIMIT 1;
    END IF;
    INSERT INTO projectBookingData_audit
    SET changedat = UTC_TIMESTAMP, bookingID = OLD.id, statement = 'DELETE';
    END";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TRIGGER projectBookingData_insert_trigger
    AFTER INSERT ON projectBookingData
    FOR EACH ROW
    BEGIN
    SELECT COUNT(*) INTO @cnt FROM projectBookingData_audit;
    IF @cnt >= 150 THEN
    DELETE FROM projectBookingData_audit ORDER BY id LIMIT 1;
    END IF;
    INSERT INTO projectBookingData_audit
    SET changedat = UTC_TIMESTAMP, bookingID = NEW.id, statement = CONCAT('INSERT ', NEW.timestampID);
    END";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE receiptBook(
        id INT(8) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        supplierID INT(6) UNSIGNED,
        taxID INT(4) UNSIGNED,
        journalID INT(10) UNSIGNED,
        invoiceDate DATETIME,
        info VARCHAR(64),
        amount DECIMAL(10,2),
        FOREIGN KEY (supplierID) REFERENCES clientData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (journalID) REFERENCES account_journal(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE closeUpData(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userID INT(6) UNSIGNED,
        lastDate DATETIME NOT NULL,
        saldo DECIMAL(6,2),
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE accountingLocks(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        companyID INT(6) UNSIGNED,
        lockDate DATE NOT NULL,
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE checkinLogs(
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        timestampID INT(10) UNSIGNED,
        remoteAddr VARCHAR(50),
        userAgent VARCHAR(150),
        FOREIGN KEY (timestampID) REFERENCES logs(indexIM)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE documents(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        docID VARCHAR(40),
        companyID INT(6) UNSIGNED,
        name VARCHAR(100) NOT NULL,
        txt MEDIUMTEXT NOT NULL,
        version VARCHAR(15) NOT NULL DEFAULT 'latest',
        isBase ENUM('TRUE', 'FALSE') NOT NULL DEFAULT 'FALSE',
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE contactPersons (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        clientID INT(6) UNSIGNED,
        firstname VARCHAR(150),
        lastname VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL,
        position VARCHAR(250),
        responsibility VARCHAR(250),
        dial VARCHAR(20),
        faxDial VARCHAR(20),
        phone VARCHAR(25),
        gender ENUM('male','female') NOT NULL,
        title VARCHAR(20),
        pgpKey TEXT,
        FOREIGN KEY (clientID) REFERENCES clientData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE documentProcess(
        id VARCHAR(16) NOT NULL PRIMARY KEY,
        docID INT(6) UNSIGNED,
        personID INT(6) UNSIGNED,
        password VARCHAR(60) NOT NULL,
        document_text MEDIUMTEXT NOT NULL,
        document_headline VARCHAR(120) NOT NULL,
        document_version VARCHAR(15) NOT NULL DEFAULT '1.0',
        FOREIGN KEY (docID) REFERENCES documents(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (personID) REFERENCES contactPersons(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE documentProcessHistory(
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        processID VARCHAR(16),
        logDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        activity VARCHAR(20) NOT NULL,
        info VARCHAR(450),
        userAgent VARCHAR(150)
    )";
    if (! $conn->query ( $sql )) {
        echo mysqli_error ( $conn );
    }

    $sql = "CREATE TABLE dsgvo_vv_templates(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        companyID INT(6) UNSIGNED,
        name VARCHAR(60) NOT NULL,
        type ENUM('base', 'app') NOT NULL,
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_vv_template_settings(
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        templateID INT(6) UNSIGNED,
        opt_name VARCHAR(30) NOT NULL,
        opt_descr VARCHAR(350) NOT NULL,
        opt_status VARCHAR(15) NOT NULL DEFAULT 'ACTIVE',
        FOREIGN KEY (templateID) REFERENCES dsgvo_vv_templates(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_vv_data_matrix (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        companyID INT(6) UNSIGNED,
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_vv_data_matrix_settings (
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        matrixID INT(6) UNSIGNED,
        opt_name VARCHAR(30) NOT NULL,
        opt_descr VARCHAR(350) NOT NULL,
        opt_status VARCHAR(15) NOT NULL DEFAULT 'ACTIVE',
        opt_duration INT(6) DEFAULT 0,
        opt_unit INT(6) DEFAULT 0,
        UNIQUE KEY (matrixID,opt_name),
        FOREIGN KEY (matrixID) REFERENCES dsgvo_vv_data_matrix(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_vv(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        templateID INT(6) UNSIGNED,
        name VARCHAR(60) NOT NULL,
        FOREIGN KEY (templateID) REFERENCES dsgvo_vv_templates(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_vv_settings(
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        vv_id INT(6) UNSIGNED,
        setting_id INT(10) UNSIGNED,
        matrix_setting_id INT(10) UNSIGNED,
        setting MEDIUMTEXT NOT NULL,
        category VARCHAR(50),
        clientID INT(6) UNSIGNED,
        FOREIGN KEY (clientID) REFERENCES clientData(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
        FOREIGN KEY (vv_id) REFERENCES dsgvo_vv(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (setting_id) REFERENCES dsgvo_vv_template_settings(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (matrix_setting_id) REFERENCES dsgvo_vv_data_matrix_settings(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE dynamicprojects(
        projectid VARCHAR(100) NOT NULL PRIMARY KEY,
        projectname VARCHAR(250) NOT NULL,
        projectdescription MEDIUMTEXT NOT NULL,
        companyid INT(6) UNSIGNED NOT NULL,
        clientid INT(6) UNSIGNED,
        clientprojectid INT(6) UNSIGNED,
        projectcolor VARCHAR(10),
        projectstart DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        projectend VARCHAR(12) DEFAULT '0000-00-00' NOT NULL,
        projectstatus ENUM('ACTIVE', 'DEACTIVATED', 'DRAFT', 'COMPLETED', 'REVIEW') DEFAULT 'ACTIVE' NOT NULL,
        projectpriority INT(6),
        projectparent VARCHAR(100),
        projectnextdate VARCHAR(12),
        projectseries MEDIUMBLOB,
        projectpercentage INT(3) DEFAULT 0 NOT NULL,
        estimatedHours VARCHAR(100) DEFAULT 0 NOT NULL,
        needsreview ENUM('TRUE','FALSE') DEFAULT 'TRUE',
        level INT(3) DEFAULT 0 NOT NULL,
        levelmax INT(3) DEFAULT 100 NOT NULL,
        projecttags VARCHAR(250) DEFAULT '' NOT NULL,
        isTemplate ENUM('TRUE','FALSE') DEFAULT 'FALSE' NOT NULL,
		projectmailheader TEXT NOT NULL DEFAULT '',
		v2 VARCHAR(150) DEFAULT NULL,
        FOREIGN KEY (companyid) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $sql = "CREATE TABLE dynamicprojectsemployees(
        projectid VARCHAR(100) NOT NULL,
        userid INT(6) UNSIGNED,
        position VARCHAR(10) DEFAULT 'normal' NOT NULL,
        PRIMARY KEY(projectid, userid),
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (userid) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $sql = "CREATE TABLE dynamicprojectsteams(
        projectid VARCHAR(100) NOT NULL,
        teamid INT(6) UNSIGNED,
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (teamid) REFERENCES teamData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE

    );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $conn->query("CREATE TABLE dynamicprojectslogs(
		id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        projectid VARCHAR(100) NOT NULL,
        activity VARCHAR(20) NOT NULL,
        logTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        userID INT(6) UNSIGNED,
        extra1 VARCHAR(250),
        extra2 VARCHAR(450),
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )");

	$sql = "CREATE TABLE dynamicprojectsnotes(
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        taskID VARCHAR(100) NOT NULL,
		userID INT(6) UNSIGNED,
        notedate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		notesubject VARCHAR(75),
        notetext VARCHAR(1000) NOT NULL,
        FOREIGN KEY (taskID) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
		FOREIGN KEY (userID) REFERENCES UserData(id)
		ON UPDATE CASCADE
		ON DELETE SET NULL
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $sql = "CREATE TABLE document_customs(
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        companyID INT(6) UNSIGNED,
        doc_id VARCHAR(40) NOT NULL,
        identifier VARCHAR(30),
        content VARCHAR(450),
        status VARCHAR(10),
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    );";
    if(!$conn->query($sql)){
        echo $conn->error;
    }
    $sql = "CREATE TABLE archiveconfig(
	id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(150) NOT NULL,
        endpoint VARCHAR(50),
        awskey VARCHAR(50),
        secret VARCHAR(50),
	isActive ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'
    )";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $sql = "CREATE TABLE emailprojects (
    id INT(10) NOT NULL AUTO_INCREMENT,
    server VARCHAR(50) NOT NULL,
    port VARCHAR(50) NOT NULL,
    service ENUM('imap','pop3') NOT NULL,
    smtpSecure ENUM('','tls','ssl') NOT NULL,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(50) NOT NULL,
    logEnabled ENUM('TRUE','FALSE') NOT NULL,
    PRIMARY KEY (id))";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE workflowRules (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		workflowID INT(10),
		templateID VARCHAR(100),
		position INT(4),
		subject VARCHAR(100),
		fromAddress VARCHAR(100),
		toAddress VARCHAR(100),
		autoResponse TEXT,
		isActive ENUM('TRUE', 'FALSE') NOT NULL DEFAULT 'TRUE',
		FOREIGN KEY (templateID) REFERENCES dynamicprojects(projectid)
		ON UPDATE CASCADE
		ON DELETE SET NULL,
		FOREIGN KEY (workflowID) REFERENCES emailprojects(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE microtasks (
        projectid varchar(100) NOT NULL,
        microtaskid varchar(100) NOT NULL,
        title varchar(50) NOT NULL,
        ischecked enum('TRUE','FALSE') NOT NULL DEFAULT 'FALSE',
        finisher int(6) DEFAULT NULL COMMENT 'user who completes this microtask',
        completed timestamp NULL DEFAULT NULL,
        PRIMARY KEY (projectid,microtaskid))";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_training_modules (
        id int(6) NOT NULL AUTO_INCREMENT,
        name varchar(100),
        PRIMARY KEY (id)
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_training (
        id int(6) NOT NULL AUTO_INCREMENT,
        name varchar(100),
        companyID INT(6) UNSIGNED,
        version INT(6) DEFAULT 0,
        onLogin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        allowOverwrite ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        moduleID int(6),
        answerEveryNDays int(6),
        random ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
        PRIMARY KEY (id),
        FOREIGN KEY (companyID) REFERENCES companyData(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (moduleID) REFERENCES dsgvo_training_modules(id) ON UPDATE CASCADE ON DELETE CASCADE)";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_training_questions (
        id int(6) NOT NULL AUTO_INCREMENT,
        title varchar(100),
        text MEDIUMTEXT,
        trainingID INT(6),
        version INT(6) DEFAULT 1,
        survey ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        PRIMARY KEY (id),
        FOREIGN KEY (trainingID) REFERENCES dsgvo_training(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_training_user_relations (
        trainingID int(6),
        userID INT(6) UNSIGNED,
        PRIMARY KEY (trainingID, userID),
        FOREIGN KEY (trainingID) REFERENCES dsgvo_training(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_training_team_relations (
        trainingID int(6),
        teamID INT(6) UNSIGNED,
        PRIMARY KEY (trainingID, teamID),
        FOREIGN KEY (trainingID) REFERENCES dsgvo_training(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (teamID) REFERENCES teamData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_training_company_relations (
        trainingID int(6),
        companyID INT(6) UNSIGNED NOT NULL,
        PRIMARY KEY (trainingID, companyID),
        FOREIGN KEY (trainingID) REFERENCES dsgvo_training(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (companyID) REFERENCES companyData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_training_completed_questions (
        questionID int(6),
        userID INT(6) UNSIGNED,
        correct ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        version INT(6) DEFAULT 0,
        tries INT(6) DEFAULT 1,
        duration INT(6) DEFAULT 0,
        lastAnswered DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (questionID, userID),
        FOREIGN KEY (questionID) REFERENCES dsgvo_training_questions(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
	if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_training_completed_questions_survey_answers (
        questionID int(6),
        userID INT(6) UNSIGNED,
        identifier VARCHAR(30) NOT NULL,
        PRIMARY KEY (questionID, userID, identifier),
        FOREIGN KEY (questionID) REFERENCES dsgvo_training_completed_questions(questionID) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
	if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE dsgvo_training_user_suspension (
        userID INT(6) UNSIGNED,
        last_suspension DATETIME DEFAULT CURRENT_TIMESTAMP,
        suspension_count INT(6) DEFAULT 0,
        PRIMARY KEY (userID),
        FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
	if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE position ( id INT(6) NOT NULL AUTO_INCREMENT,
        name VARCHAR(20) NOT NULL,
        PRIMARY KEY (id))";

    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        $conn->query("INSERT INTO position (name) VALUES ('GF'),('Management'),('Leitung')");
    }

    $sql = "CREATE TABLE emailprojectlogs(
        id int(11) NOT NULL AUTO_INCREMENT,
        timeofoccurence timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        body longtext,
        PRIMARY KEY (id)
        )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }


    $sql = "CREATE TABLE security_modules(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        module VARCHAR(50) NOT NULL,
        recentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        symmetricKey VARCHAR(150) NOT NULL,
        publicKey VARCHAR(150) NOT NULL,
        outDated ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
		checkSum VARCHAR(100)
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE security_access(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userID INT(6) UNSIGNED,
        module VARCHAR(50) NOT NULL,
        optionalID VARCHAR(32),
        privateKey VARCHAR(150) NOT NULL,
        recentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        outDated ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE security_company(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        companyID INT(6) UNSIGNED NOT NULL,
		publicKey VARCHAR(150) NOT NULL,
		symmetricKey VARCHAR(150) NOT NULL,
        recentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        outDated ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE security_projects(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        projectID INT(6) UNSIGNED,
        publicKey VARCHAR(150) NOT NULL,
        symmetricKey VARCHAR(150) NOT NULL,
        recentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        outDated ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        FOREIGN KEY (projectID) REFERENCES projectData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

	$sql = "CREATE TABLE security_users(
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		userID INT(6) UNSIGNED,
		publicKey VARCHAR(150) NOT NULL,
		privateKey VARCHAR(150) NOT NULL,
		recentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        outDated ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' NOT NULL,
		checkSum VARCHAR(100),
		FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
	)";
	if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $conn->query("CREATE TABLE dsgvo_vv_logs (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(6) UNSIGNED,
		vvID VARCHAR(10),
        log_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        short_description VARCHAR(100) NOT NULL,
        scope VARCHAR(100),
        long_description TEXT,
        FOREIGN KEY (user_id) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )");
    if($conn->error){
        echo $conn->error;
    }

    $sql = "CREATE TABLE external_users(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        contactID INT(6) UNSIGNED,
        login_mail VARCHAR(120) UNIQUE NOT NULL,
        login_pw VARCHAR(120) NOT NULL,
        publicKey VARCHAR(100) NOT NULL,
        privateKey VARCHAR(100) NOT NULL,
        entryDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        lastPswChange DATETIME DEFAULT NULL,
        FOREIGN KEY (contactID) REFERENCES contactPersons(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE security_external_access(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        externalID INT(6) UNSIGNED,
        module VARCHAR(50) NOT NULL,
        optionalID VARCHAR(32),
        privateKey VARCHAR(150) NOT NULL,
        recentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        outDated ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        FOREIGN KEY (externalID) REFERENCES external_users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE relationship_project_user(
        projectID INT(6) UNSIGNED,
        userID INT(6) UNSIGNED,
        access VARCHAR(10) NOT NULL DEFAULT 'READ',
        expirationDate DATE NOT NULL DEFAULT '0000-00-00',
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (projectID) REFERENCES projectData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE relationship_project_extern(
        projectID INT(6) UNSIGNED,
        userID INT(6) UNSIGNED,
        access VARCHAR(10) NOT NULL DEFAULT 'READ',
        expirationDate DATE NOT NULL DEFAULT '0000-00-00',
        FOREIGN KEY (userID) REFERENCES external_users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (projectID) REFERENCES projectData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }

	$sql = "CREATE TABLE folder_default_sturctures(
		id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		category VARCHAR(20) NOT NULL,
		categoryID VARCHAR(20) NOT NULL,
		name VARCHAR(155) NOT NULL
	)";
	if(!$conn->query($sql)){
		echo $conn->error;
	}

	$sql = "CREATE TABLE archive(
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		uniqID VARCHAR(30) UNIQUE,
		category VARCHAR(20) NOT NULL,
		categoryID VARCHAR(20) NOT NULL,
        name VARCHAR(120) NOT NULL,
        parent_directory VARCHAR(120) NOT NULL DEFAULT 'ROOT',
        type VARCHAR(10) NOT NULL,
        uploadDate DATETIME DEFAULT CURRENT_TIMESTAMP,
		uploadUser INT(6)
    )";
	if(!$conn->query($sql)){
		echo $conn->error;
	}

	$sql = "CREATE TABLE archive_meta(
		id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		archiveID INT(10) UNSIGNED,
		parentID INT(10),
		name VARCHAR(400) NOT NULL,
		description MEDIUMTEXT NOT NULL DEFAULT '',
		category VARCHAR(120),
		status VARCHAR(100) NOT NULL DEFAULT 'PENDING',
		fromDate DATE NOT NULL DEFAULT '0000-00-00',
		toDate DATE NOT NULL DEFAULT '0000-00-00',
		validDate DATE NOT NULL DEFAULT '0000-00-00',
		version INT(4) NOT NULL DEFAULT 1,
		versionDescr VARCHAR(250),
		cPartner VARCHAR(50),
		cPartnerID VARCHAR(20),
		note VARCHAR(500) NOT NULL DEFAULT '',
		FOREIGN KEY (archiveID) REFERENCES archive(id)
		ON UPDATE CASCADE ON DELETE CASCADE
	)";
	if(!$conn->query($sql)){
		echo $conn->error;
	}

	$conn->query("CREATE TABLE dsgvo_categories(
		id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		name VARCHAR(250) NOT NULL
	)");
	if($conn->error){
		echo $conn->error;
	}

	$conn->query("CREATE TABLE messenger_conversations(
		id INT(6) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		identifier VARCHAR(13) UNIQUE NOT NULL,
		subject VARCHAR(550) NOT NULL,
		category VARCHAR(25),
		categoryID VARCHAR(20)
	)");
	if($conn->error){
		echo $conn->error;
	}

	$conn->query("CREATE TABLE relationship_conversation_participant(
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		conversationID INT(6) UNSIGNED,
		partType VARCHAR(25) NOT NULL,
		partID VARCHAR(50) NOT NULL,
		status VARCHAR(25) NOT NULL DEFAULT 'normal' COMMENT 'exited, creator, normal, open',
		lastCheck DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		archive VARCHAR(45),
		FOREIGN KEY (conversationID) REFERENCES messenger_conversations(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
		UNIQUE KEY relationship (conversationID, partType, partID)
	)");
	if($conn->error){
		echo $conn->error;
	}

	$conn->query("CREATE TABLE messenger_messages(
		id INT(15) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		message MEDIUMTEXT,
		participantID INT(10) UNSIGNED,
		sentTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		type VARCHAR(15) NOT NULL DEFAULT 'text',
		vKey VARCHAR(150),
		FOREIGN KEY (participantID) REFERENCES relationship_conversation_participant(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
		UNIQUE KEY uq_participant_time (participantID, sentTime)
	)");
	if($conn->error){
		echo $conn->error;
    }

    $sql = "CREATE TABLE access_permission_groups (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(20) NOT NULL,
        parent INT(10) UNSIGNED,
        FOREIGN KEY (parent) REFERENCES access_permission_groups(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE access_permissions (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        groupID INT(10) UNSIGNED NOT NULL,
        name VARCHAR(30) NOT NULL,
        FOREIGN KEY (groupID) REFERENCES access_permission_groups(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE relationship_access_permissions (
        userID INT(6) UNSIGNED NOT NULL,
        permissionID INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (userID, permissionID),
        FOREIGN KEY (permissionID) REFERENCES access_permissions(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE relationship_team_access_permissions (
        teamID INT(6) UNSIGNED NOT NULL,
        permissionID INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (teamID, permissionID),
        FOREIGN KEY (permissionID) REFERENCES access_permissions(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (teamID) REFERENCES teamData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE default_access_permissions (
        permissionID INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (permissionID),
        FOREIGN KEY (permissionID) REFERENCES access_permissions(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

	$sql = "CREATE TABLE tags(
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		value VARCHAR(50) NOT NULL,
		type VARCHAR(20) NOT NULL DEFAULT 'text',
		extra VARCHAR(80)
	)";
	if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

}
