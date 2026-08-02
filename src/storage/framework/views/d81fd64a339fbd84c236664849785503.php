<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mohamad Afizi's Portfolio Website</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css?family=Montserrat:900" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.waves.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/baffle@0.3.6/dist/baffle.min.js"></script>
<link rel="icon" type="image/png" href="../../shared/media/images/dp.png">
<style>
  body {
    margin: 0;
    background: #000;
    color: #ddd;
    font-family: "Inter", "Segoe UI", sans-serif;
    line-height: 1.6;
    scroll-behavior: smooth;
  }

  #vanta-bg {
    position: fixed;
    inset: 0;
    z-index: 0;
    width: 100vw;
    height: 100vh;
  }

  .main-content-z {
    position: relative;
    z-index: 1;
    min-height: 100vh;
  }

  section {
    padding: 40px 20px;
    max-width: 1200px;
    margin: auto;
  }

  .nav {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 1vh;
    box-sizing: border-box;
  }

  .intro h2 {
    font-size: 1rem;
    color: #aaa;
    margin-top: -5px;
    margin-bottom: 5px;
    font-weight: 400;
  }

  .intro p {
    color: #ccc;
    font-size: 0.8rem;
    margin-bottom: 25px;
    text-align: justify;
  }

  .nav-links {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: -20px;
  }

  .nav-links a {
    font-size: 1.5rem;
    text-decoration: none;
    color: #555;
    font-weight: 500;
    transition: color 0.3s ease, transform 0.3s ease;
  }

  .nav-links a:hover {
    color: #fff;
    transform: translateY(-3px);
  }

  .nav-links a.active {
    color: #fff;
    transform: translateY(-3px);
  }

  .mr-1 {
    margin-right: 0.25rem;
  }

  .skill-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: -10px;
    margin-bottom: 20px;
    justify-content: flex-start;
  }

  .skill-tag {
    background: #222;
    color: #bbb;
    padding: 2px 10px;
    border-radius: 16px;
    font-size: 0.75rem;
    font-weight: 500;
    border: 1px solid #333;
    opacity: 0;
    transform: translateY(10px);
    animation: fadeIn 0.5s ease-out forwards;
  }

  @keyframes fadeIn {
    to { opacity: 1; transform: translateY(0); }
  }

  .text-container {
    display: flex;
    align-items: flex-start;
    flex-direction: column;
  }

  .text-container h1 {
    margin: 0;
    font-family: 'Montserrat', sans-serif;
    font-size: 90px;
    letter-spacing: 0vw;
    text-align: left;
    color: white;
    margin-bottom: -25px;
  }

  .text__glitch {
    font-size: 30px;
    letter-spacing: 1px;
    text-align: center;
    text-transform: uppercase;
  }

  .site-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 20;
    padding: 4px 0;
    text-align: center;
    color: #6b7280;
    font-size: 9px;
  }

  .site-footer a {
    margin-left: 0.5rem;
    color: #6b7280;
    transition: color 0.3s ease;
  }

  .site-footer a:hover {
    color: #fff;
  }

  .chart-container {
    margin-top: 30px;
    margin-bottom: 50px;
    margin-left: auto;
    margin-right: auto;
    height: 240px;
  }

  .cards-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 10px;
  }

  .cards-wrapper {
    display: block;
  }

  .cards-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-top: 14px;
  }

  .cards-pagination .page-btn {
    background: rgba(255,255,255,0.06);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.06);
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 1.1rem;
    cursor: pointer;
    min-width: 36px;
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .cards-pagination .page-btn:disabled {
    opacity: 0.35;
    cursor: default;
  }

  .cards-pagination .page-info {
    color: #9ca3af;
    font-size: 0.95rem;
  }

  .card {
    background: rgba(26, 26, 26, 0.8);
    border: 1px solid #333;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.5s ease-in-out;
    opacity: 0;
    transform: translateY(20px);
  }

  .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
  }

  .card.animate {
      opacity: 1;
      transform: translateY(0);
  }

  .card.exit {
      opacity: 0;
      transform: translateY(20px) scale(0.95);
  }

  .card h3 {
    margin-top: 0;
    color: #fff;
  }

  .card p {
    font-size: 0.9rem;
    color: #fff;
    text-align: left;
  }

  .card-screenshot {
    width: 100%;
    height: 160px;
    object-fit: cover;
    border-radius: 8px;
    margin: 15px 0;
    border: 1px solid #444;
  }

  .card-techstack {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 12px 0;
  }

  .tech-pill {
    background: rgba(109, 239, 248, 0.15);
    border: 1px solid rgba(109, 239, 248, 0.3);
    color: #6defF8;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 0.75rem;
    font-weight: 500;
    display: inline-block;
    white-space: nowrap;
  }

  @media (max-width: 768px) {
    .cards-grid {
      grid-template-columns: 1fr;
    }

    .text-container h1 {
      font-size: 50px;
    }

    section {
      padding: 20px 10px;
    }

    .chart-container {
      height: 200px;
    }

    .nav-links a {
      font-size: 1.2rem;
    }

    .nav-links {
      gap: 12px;
    }
  }

  @media (max-width: 480px) {
    .text-container h1 {
      font-size: 40px;
    }

    section {
      padding: 15px 5px;
    }

    .chart-container {
      height: 150px;
    }

    .nav-links a {
      font-size: 1rem;
    }

    .nav-links {
      gap: 8px;
    }

    .skill-tags {
      gap: 4px;
    }

    .card {
      padding: 15px;
    }
  }

  .card-links {
    display: flex;
    justify-content: flex-end;
    margin-top: 15px;
  }

  .card-link {
    color: #fff;
    font-weight: bold;
    text-decoration: none;
    cursor: pointer;
    transition: color 0.3s ease;
    margin-left: 12px;
    font-size: 0.9rem;
  }

  .card-link:hover {
    color: #0ff;
    text-decoration: underline;
  }

  .modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
  }

  .modal.visible {
    opacity: 1;
    visibility: visible;
  }

  .modal-content {
    background: #111;
    border-radius: 12px;
    box-shadow: 0 0 30px rgba(109, 239, 248, 0.3);
    width: 90%;
    max-width: 672px;
    padding: 24px;
    position: relative;
    border: 1px solid #4a5568;
    max-height: 60vh;
    overflow-y: auto;
  }

  .modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    color: #a0aec0;
    font-size: 2rem;
    cursor: pointer;
    line-height: 1;
  }

  .page-doc-btn {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 100;
    background: rgba(109, 239, 248, 0.1);
    border: 1px solid rgba(109, 239, 248, 0.3);
    color: #6defF8;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .page-doc-btn:hover {
    background: rgba(109, 239, 248, 0.2);
    border-color: rgba(109, 239, 248, 0.5);
    box-shadow: 0 0 15px rgba(109, 239, 248, 0.2);
  }

  @media (max-width: 480px) {
    .page-doc-btn {
      top: 10px;
      right: 10px;
      padding: 6px 12px;
      font-size: 0.8rem;
    }
  }

  .category-description {
    margin-top: 20px;
    margin-bottom: 30px;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: 0;
    color: #cbd5e0;
    font-size: 0.9rem;
    line-height: 1.6;
    opacity: 0;
    transform: translateY(-10px);
    transition: opacity 0.3s ease, transform 0.3s ease;
  }

  .category-description.active {
    opacity: 1;
    transform: translateY(0);
  }

  .grouping-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
    margin-bottom: 0px;
    opacity: 0;
    transition: opacity 0.5s ease;
  }

  .grouping-filter.animate {
    opacity: 1;
  }

  .group-btn {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #999;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .group-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    color: #ddd;
  }

  .group-btn.active {
    background: rgba(109, 239, 248, 0.2);
    border-color: rgba(109, 239, 248, 0.4);
    color: #6defF8;
  }
</style>
</head>
<body>
<div id="vanta-bg"></div>
<!-- <button id="pageDocBtn" class="page-doc-btn">Resume</button> -->
<div class="main-content-z">
  <section class="nav" id="home">
    <div class="intro">
      <div class="text-container">
        <h1 class="text__glitch">MOHAMAD AFIZI</h1>
      </div>
      <h2><span class="fas fa-code mr-1"></span> Full Stack Developer | Kuala Lumpur, Malaysia | fizzyjamal@gmail.com</h2>

      <p id="profile-summary" style="font-size: 14px;">
        A Full Stack Developer based in Kuala Lumpur with hands-on experience in building web applications, internal systems, and data-driven tools. Proficient in PHP, Laravel, JavaScript, and SQL — with a passion for clean code, practical solutions, and continuous learning across both frontend and backend disciplines.
      </p>

      <div class="skill-tags" id="skill-tags">
        <!-- Hardcoded skill tags -->
      </div>

      <!-- Chart.js Canvas -->
      <div class="chart-container">
        <h3 style="text-align: center; margin-bottom: 15px;">
          <span id="analyticsSliderTitle" style="color: #fff;">Visitor Analytics</span>
        </h3>
        <canvas id="visitorAnalyticsChart"></canvas>
      </div>

      <nav class="nav-links">
        <a href="#projects" data-category="projects">projects</a>
        <a href="#milestones" data-category="milestones">milestones</a>
        <a href="#industry_experiences" data-category="industry_experiences">industry experiences</a>
      </nav>

      <div id="categoryDescription" class="category-description active">
        Explore the projects I've worked on, from solo ventures to collaborative efforts.
      </div>

      <div id="groupingFilter" class="grouping-filter"></div>

      <div class="cards-wrapper">
        <div class="cards-grid">
          <div class="card"><h3>Card 1</h3><p></p><div class="card-techstack"></div><div class="card-links"></div></div>
          <div class="card"><h3>Card 2</h3><p></p><div class="card-techstack"></div><div class="card-links"></div></div>
          <div class="card"><h3>Card 3</h3><p></p><div class="card-techstack"></div><div class="card-links"></div></div>
          <div class="card"><h3>Card 4</h3><p></p><div class="card-techstack"></div><div class="card-links"></div></div>
          <div class="card"><h3>Card 5</h3><p></p><div class="card-techstack"></div><div class="card-links"></div></div>
          <div class="card"><h3>Card 6</h3><p></p><div class="card-techstack"></div><div class="card-links"></div></div>
        </div>

        <div class="cards-pagination" aria-label="Cards pagination">
          <button id="cardsPrev" class="page-btn" aria-label="Previous page">&lt;</button>
          <span id="cardsPageInfo" class="page-info" aria-hidden="true"></span>
          <button id="cardsNext" class="page-btn" aria-label="Next page">&gt;</button>
        </div>
      </div>

    </div>
  </section>
</div>

<!-- Modal Structure -->
<div id="detailsModal" class="modal">
  <div class="modal-content">
    <span id="closeModalBtn" class="modal-close">&times;</span>
    <h2 id="modalTitle" style="color:#6defF8; font-size: 1.5rem; font-weight: bold; margin-top: 0; margin-bottom: 1rem;"></h2>
    <div id="modalBody" style="color: #cbd5e0; line-height: 1.6;"></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    VANTA.WAVES({
        el: "#vanta-bg",
        mouseControls: true,
        touchControls: true,
        gyroControls: false,
        minHeight: 200.00,
        minWidth: 200.00,
        scale: 1.00,
        scaleMobile: 1.00,
        color: 0x0,
        shininess: 7.00,
        waveHeight: 9.50,
        waveSpeed: 1.50,
        zoom: 0.65
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const text = baffle(".text__glitch");
    text.set({ characters: "█▓█ ▒░/▒░ █░▒▓/ █▒▒ ▓▒▓/█ ░█▒/ ▒▓░ █<░▒ ▓/░>", speed: 50 });
    text.start();
    text.reveal(1000);
});

document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-links a');
    const cards = document.querySelectorAll('.cards-grid .card');
    let isAnimating = false;

    function setActiveNavLink(linkElement) {
        navLinks.forEach(link => link.classList.remove('active'));
        linkElement.classList.add('active');
    }

    const itemsPerPage = 6;

    const categoriesData = {
      'projects': [
        { title: 'Library Book System', group: 'Independent Projects', description: 'A Laravel-based library application demonstrating MVC workflow with SQLite database integration, featuring book listing and pagination.', techStack: ['Laravel', 'SQLite', 'Git'] },
        { title: 'LPT Highway Traffic Analysis', group: 'Independent Projects', description: 'Data analytics project analyzing traffic flow patterns at Gombak Toll Plaza using Microsoft Excel and custom datasets.', techStack: ['Data Analytics', 'Visualization', 'Microsoft Excel'] },
        { title: 'Portfolio V3 CMS', group: 'Independent Projects', description: 'A content management system for portfolio website with full CRUD functionality, login security, and SQLite backend.', techStack: ['PHP', 'SQLite'] },
        { title: 'Portfolio Website V3', group: 'Independent Projects', description: 'Dynamic portfolio website with API-driven content rendering, visitor analytics, and Chart.js visualizations.', techStack: ['PHP', 'SQLite', 'API', 'JavaScript'] },
        { title: 'Portfolio Website V2', group: 'Independent Projects', description: 'PHP-based static portfolio hosted on on-premise mini server with Apache web server integration.', techStack: ['PHP'] },
        { title: 'Linux On-Premise 24/7 Server', group: 'Independent Projects', description: 'Repurposed laptop transformed into a self-managed Linux server for hands-on learning in deployment and system management.', techStack: ['Linux', 'Apache', 'SFTP', 'Domain'] },
        { title: 'Besikuning Dashboard', group: 'Independent Projects', description: 'Real-time gold market insights dashboard for Malaysian traders hosted on AWS S3 with Google Analytics.', techStack: ['PHP', 'AWS S3', 'Google Analytics'] },
        { title: 'Portfolio Website V1', group: 'Independent Projects', description: 'Minimalist HTML portfolio showcasing projects with clean black-and-white design hosted on GitHub Pages.', techStack: ['HTML', 'GitHub Pages'] },
        { title: 'Media & Multimedia Design', group: 'Independent Projects', description: 'Event media design including posters, banners, and promotional videos for university activities.', techStack: ['Graphic Design', 'Video Editing'] },
        { title: 'Laravel Breeze Auth Sandbox', group: 'Technical Experiments', description: 'Isolated Laravel test app validating Breeze authentication flow including register, login, logout, and middleware route protection.', techStack: ['Laravel', 'Breeze', 'SQLite'] },
        { title: 'REST API Token Lab', group: 'Technical Experiments', description: 'Small experiment project focused on issuing API tokens and validating protected endpoint access with basic role checks.', techStack: ['Laravel', 'API', 'Postman'] },
        { title: 'Queue Worker Mini Lab', group: 'Technical Experiments', description: 'Sandbox setup to test queue job dispatch, worker processing, retry behavior, and failed-job handling.', techStack: ['Laravel', 'Queues', 'Redis'] },
        { title: 'Finance Data Audit', group: 'Guided Projects', description: 'Hands-on financial data analysis using ACL v9 with data import, verification, and anomaly detection.', techStack: ['Data Audit', 'ACL'] },
        { title: 'Interactive Excel Sales Dashboard', group: 'Guided Projects', description: 'Advanced Excel dashboard with large dataset handling, pivot tables, and data visualization techniques.', techStack: ['Data Analytics', 'Visualization', 'Microsoft Excel'] },
        { title: 'IMR665 - Audio-Visual Records Management', group: 'Academic Projects', description: 'Documentary video production on Nasi Ambeng with editing, animations, subtitles, and sound effects.', techStack: ['Video Editing', 'Documentary'] },
        { title: 'IMR664 - Electronic Record Keeping', group: 'Academic Projects', description: 'Business plan development for electronic records management system using Kordil EDMS.', techStack: ['EDMS'] },
        { title: 'IMS457 - Multimedia for Information Professionals', group: 'Academic Projects', description: 'Interactive multimedia presentation titled "Eurotrip" developed with Macromedia Director and Adobe tools.', techStack: ['Swish Max', 'Adobe Photoshop'] },
        { title: 'IMR606 - Digitization of Records', group: 'Academic Projects', description: 'Visual Basic application with Microsoft Access integration for student-job matching system.', techStack: ['Visual Basic', 'Microsoft Access'] },
        { title: 'IMA656 - E-Learning Instruction Design', group: 'Academic Projects', description: '3R Awareness multimedia presentation promoting environmental consciousness with interactive design.', techStack: ['Swish Max', 'Adobe Photoshop'] },
        { title: 'IMS506 - Database for Information Management', group: 'Academic Projects', description: 'Sport Centre Management System built with Microsoft Access including members, facilities, and bookings.', techStack: ['Microsoft Access'] },
        { title: 'IMR555 - Electronic Records System', group: 'Academic Projects', description: 'Tuition centre management system with student registration, payments, attendance, and performance tracking.', techStack: ['Visual Basic', 'Microsoft Access'] },
        { title: 'IMR505 - Records & Archival Repositories', group: 'Academic Projects', description: 'Complete records centre business plan with logo design and floor layout planning.', techStack: ['Adobe Illustrator'] },
        { title: 'IMS456 - Web Design & Content Management', group: 'Academic Projects', description: 'Personal and university websites using HTML and CSS with media integration.', techStack: ['HTML', 'CSS', 'JavaScript'] }
      ],
      'milestones': [
        { title: 'W3Schools', group: 'Learning Paths', description: 'Completed beginner Excel course covering core spreadsheet fundamentals and essential data organization skills.', techStack: [] },
        { title: 'Microsoft Learn', group: 'Learning Paths', description: 'Showcasing completed Azure paths, badges, and learning achievements in cloud computing and enterprise solutions.', techStack: [] },
        { title: 'Salesforce Trailblazer', group: 'Learning Paths', description: 'Showcasing Salesforce trails, badges, and community engagement across CRM and business cloud platforms.', techStack: [] },
        { title: 'Google Analytics for Beginners', group: 'Skill-Validated Certifications', description: 'Google Analytics Academy certification demonstrating proficiency in web analytics, traffic tracking, and data-driven insights.', techStack: [] },
        { title: 'Excel Advanced Formulas and Functions', group: 'Course Completion', description: 'Advanced Excel functions including VLOOKUP, INDEX-MATCH, and array formulas. Focuses on data analysis, complex calculations, and automating tasks using advanced formulas.', techStack: [] },
        { title: 'Power BI Essential Training', group: 'Course Completion', description: 'Introduction to Power BI covering data preparation, data modeling, DAX (Data Analysis Expressions), and creating interactive dashboards and reports.', techStack: [] },
        { title: 'SQL for Data Analysis', group: 'Course Completion', description: 'Learn SQL for querying and analyzing data with SELECT statements, JOINs, subqueries, and aggregate functions to extract insights from databases.', techStack: [] },
        { title: 'Tableau Essential Training', group: 'Course Completion', description: 'Data visualization using Tableau including connecting to data sources, creating various charts, building dashboards, and sharing insights.', techStack: [] },
        { title: 'SQL Essential Training', group: 'Course Completion', description: 'Basics of SQL including writing SELECT queries, using JOINs, and performing data aggregation for relational databases.', techStack: [] },
        { title: 'NoSQL Essential Training', group: 'Course Completion', description: 'Introduction to NoSQL databases covering their types, advantages, and methods for storing and retrieving unstructured data.', techStack: [] },
        { title: 'Microsoft Access Essential Training', group: 'Course Completion', description: 'Database management using Microsoft Access including creating tables, queries, forms, and reports to manage and analyze data.', techStack: [] },
        { title: 'Windows Server 2022 Essential Training', group: 'Course Completion', description: 'Installation, configuration, and management of Windows Server 2022 including Active Directory, networking, and server maintenance.', techStack: [] },
        { title: 'SEO Foundations', group: 'Course Completion', description: 'Introduction to Search Engine Optimization covering keyword research, on-page and off-page optimization, and SEO strategies for website visibility.', techStack: [] },
        { title: 'Social Media Marketing Foundations', group: 'Course Completion', description: 'Basics of social media marketing including creating content, engaging with audiences, and measuring the effectiveness of campaigns.', techStack: [] },
        { title: 'Digital Marketing Foundations', group: 'Course Completion', description: 'Overview of digital marketing strategies including email marketing, content marketing, and online advertising to drive traffic and conversions.', techStack: [] },
        { title: 'Google Analytics 4 (GA4) Essential Training', group: 'Course Completion', description: 'Using Google Analytics 4 to track and analyze website traffic. Covers GA4 setup, report creation, and data interpretation for informed decisions.', techStack: [] },
        { title: 'AWS Essential Training for Developers', group: 'Course Completion', description: 'Basics of Amazon Web Services for developers including cloud computing concepts, AWS services, and deploying applications on AWS.', techStack: [] },
        { title: 'Azure Administration Essential Training', group: 'Course Completion', description: 'Introduction to Microsoft Azure covering management of Azure resources, virtual machines, and networking services.', techStack: [] },
        { title: 'Google Cloud Foundations', group: 'Course Completion', description: 'Introduction to Google Cloud Platform covering core services, cloud computing concepts, and deploying applications on GCP.', techStack: [] },
        { title: 'Learning Alibaba Cloud', group: 'Course Completion', description: 'Basics of Alibaba Cloud covering services, cloud architecture, and deploying and managing applications on the platform.', techStack: [] },
        { title: 'Learning Tinkercad', group: 'Course Completion', description: '3D design and modeling with Tinkercad including creating 3D models, using basic shapes, and designing for 3D printing.', techStack: [] }
      ],
      'industry_experiences': [
        { title: 'Task 1: Role-Based Access Control for Return Order Workflow', group: 'Application Analyst & Developer', description: 'Implemented role-based access control restricting "Confirm" button to team leaders in return order workflow using PHP.', techStack: ['PHP'] },
        { title: 'Task 2: Customer Purchase Order Enhancement', group: 'Application Analyst & Developer', description: 'Enhanced PO processing with interactive IRBM validation modal, displaying multi-table joined data.', techStack: ['PHP'] },
        { title: 'Task 3: Email-to-Agency Inventory Matching Module', group: 'Application Analyst & Developer', description: 'Improved matching logic with agency-first display structure and searchable, sortable interface.', techStack: ['PHP'] },
        { title: 'Task 4: Galileo SFA – Web Tablet Application', group: 'Application Analyst & Developer', description: 'Laravel-based Sales Force Automation system with customer visit planning, inventory checks, and dynamic pricing.', techStack: ['Laravel'] },
        { title: 'Task 5: B2B SKU Maintenance Module Enhancement', group: 'Application Analyst & Developer', description: 'Enhanced SKU management with barcode support and data export functionality.', techStack: ['Laravel'] },
        { title: 'Task 6: B2B LHM Customer PO Processing', group: 'Application Analyst & Developer', description: 'Automated PO workflow with multi-format support, UOM conversions, and data validation.', techStack: ['PHP'] },
        { title: 'Task 7: Automated Daily VM Backup', group: 'Application Analyst & Developer', description: 'Automated Ubuntu VM backup routine using Windows batch scripts with compression and cleanup.', techStack: ['Windows Batch', 'VirtualBox'] },
        { title: 'Task 8: Customer Data Update Logic Enhancement', group: 'Application Analyst & Developer', description: 'Improved update logic with staged processing and audit trails to maintain data integrity.', techStack: ['Laravel'] },
        { title: 'Task 9: B2B LHS Purchase Order Processing', group: 'Application Analyst & Developer', description: 'Multi-retailer PO format support with file uploads, data transformation, and maintenance pages.', techStack: ['PHP'] },
        { title: 'Task 10: Daily CN Return Management Module', group: 'Application Analyst & Developer', description: 'Centralized module for CN return records with filtering, pagination, and bulk export capabilities.', techStack: ['PHP'] }
      ]
    };

    const categoryDescriptions = {
      'projects': 'A range of projects across independent builds, technical experiments, and guided learning — showcasing how I think, solve, and execute.',
      'milestones': 'Certifications, courses, and structured learning milestones that reflect my commitment to continuous improvement',
      'industry_experiences': 'Real-world contributions from my time in the industry — practical tasks, problem-solving, and hands-on experience across different roles and environments.'
    };

    const categoryGroups = {
      'projects': ['Independent Projects', 'Technical Experiments', 'Guided Projects', 'Academic Projects'],
      'milestones': ['Learning Paths', 'Skill-Validated Certifications', 'Course Completion'],
      'industry_experiences': ['Application Analyst & Developer']
    };

    const activeGroupPerCategory = {};
    const pagesState = {};

    function updateGroupingButtons(category) {
      const groupFilter = document.getElementById('groupingFilter');
      if (!groupFilter) return;
      groupFilter.innerHTML = '';
      const groups = categoryGroups[category] || [];
      const activeGroup = activeGroupPerCategory[category];
      if (groups.length <= 1) { groupFilter.style.display = 'none'; return; }
      groupFilter.style.display = 'flex';
      groups.forEach(group => {
        const btn = document.createElement('button');
        btn.className = 'group-btn';
        btn.textContent = group;
        btn.dataset.group = group;
        if (activeGroup === group) btn.classList.add('active');
        btn.addEventListener('click', () => {
          activeGroupPerCategory[category] = (activeGroupPerCategory[category] === group) ? null : group;
          pagesState[category] = 0;
          animateCards(category);
        });
        groupFilter.appendChild(btn);
      });
    }

    function getTotalPages(category) {
      let items = categoriesData[category] || [];
      const selectedGroup = activeGroupPerCategory[category];
      if (selectedGroup) items = items.filter(item => item.group === selectedGroup);
      return Math.max(1, Math.ceil(items.length / itemsPerPage));
    }

    function updateCardContent(category) {
      let items = categoriesData[category] || [];
      const selectedGroup = activeGroupPerCategory[category];
      if (selectedGroup) items = items.filter(item => item.group === selectedGroup);
      const page = pagesState[category] || 0;
      const pageItems = items.slice(page * itemsPerPage, (page + 1) * itemsPerPage);

      cards.forEach((card, index) => {
        const cardTitle = card.querySelector('h3');
        const cardText = card.querySelector('p');
        const cardTechstack = card.querySelector('.card-techstack');
        const cardLinks = card.querySelector('.card-links');
        if (index < pageItems.length) {
          card.style.display = '';
          const item = pageItems[index];
          if (cardTitle) cardTitle.textContent = item.title;
          if (cardText) cardText.textContent = item.description || '';
          if (cardTechstack) {
            cardTechstack.innerHTML = '';
            (item.techStack || []).forEach(skill => {
              const pill = document.createElement('span');
              pill.className = 'tech-pill';
              pill.textContent = skill;
              cardTechstack.appendChild(pill);
            });
          }
          if (cardLinks) {
            cardLinks.innerHTML = '';
            const docLink = document.createElement('a');
            docLink.href = 'javascript:void(0)';
            docLink.className = 'card-link';
            docLink.textContent = 'Details';
            docLink.onclick = () => openModal(item.title, item.description);
            cardLinks.appendChild(docLink);
            const demoLink = document.createElement('a');
            demoLink.href = '#';
            demoLink.className = 'card-link';
            demoLink.textContent = 'Demo';
            demoLink.onclick = (e) => e.preventDefault();
            cardLinks.appendChild(demoLink);
          }
        } else {
          card.style.display = 'none';
        }
      });
      updatePaginationControls(category);
    }

    function updatePaginationControls(category) {
      const totalPages = getTotalPages(category);
      const page = pagesState[category] || 0;
      const prevBtn = document.getElementById('cardsPrev');
      const nextBtn = document.getElementById('cardsNext');
      const pageInfo = document.getElementById('cardsPageInfo');
      if (prevBtn) prevBtn.disabled = page <= 0;
      if (nextBtn) nextBtn.disabled = page >= (totalPages - 1);
      if (pageInfo) pageInfo.textContent = totalPages > 1 ? `Page ${page + 1} / ${totalPages}` : '';
    }

    function ensureCategoryState(category) {
      if (typeof pagesState[category] === 'undefined') pagesState[category] = 0;
    }

    function animateCards(category) {
      isAnimating = true;
      const descElement = document.getElementById('categoryDescription');
      if (descElement) {
        descElement.classList.remove('active');
        setTimeout(() => {
          descElement.textContent = categoryDescriptions[category] || '';
          descElement.classList.add('active');
        }, 150);
      }
      updateGroupingButtons(category);
      cards.forEach((card, index) => {
        setTimeout(() => { card.classList.remove('animate'); card.classList.add('exit'); }, index * 80);
      });
      setTimeout(() => {
        updateCardContent(category);
        cards.forEach(card => { card.classList.remove('animate', 'exit'); void card.offsetWidth; });
        cards.forEach((card, index) => {
          setTimeout(() => { card.classList.add('animate'); }, index * 100);
        });
        setTimeout(() => {
          const groupFilter = document.getElementById('groupingFilter');
          if (groupFilter) {
            groupFilter.classList.remove('animate');
            void groupFilter.offsetWidth;
            groupFilter.classList.add('animate');
          }
          isAnimating = false;
        }, 1000);
      }, 600);
    }

    navLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        setActiveNavLink(e.currentTarget);
        const category = e.currentTarget.dataset.category;
        activeGroupPerCategory[category] = null;
        pagesState[category] = 0;
        animateCards(category);
      });
    });

    const prevBtn = document.getElementById('cardsPrev');
    const nextBtn = document.getElementById('cardsNext');
    if (prevBtn) prevBtn.addEventListener('click', () => {
      const active = document.querySelector('.nav-links a.active');
      const category = active ? active.dataset.category : 'projects';
      ensureCategoryState(category);
      if (pagesState[category] > 0) { pagesState[category] -= 1; animateCards(category); }
    });
    if (nextBtn) nextBtn.addEventListener('click', () => {
      const active = document.querySelector('.nav-links a.active');
      const category = active ? active.dataset.category : 'projects';
      ensureCategoryState(category);
      if (pagesState[category] < getTotalPages(category) - 1) { pagesState[category] += 1; animateCards(category); }
    });

    ensureCategoryState('projects');
    animateCards('projects');
    setActiveNavLink(document.querySelector('.nav-links a[data-category="projects"]'));
});

// Modal
const modal = document.getElementById('detailsModal');
const modalTitle = document.getElementById('modalTitle');
const modalBody = document.getElementById('modalBody');
const closeModalBtn = document.getElementById('closeModalBtn');

function openModal(title, content) {
    modalTitle.textContent = title;
    modalBody.innerHTML = content;
    modal.classList.add('visible');
}

function closeModal() {
    modal.classList.remove('visible');
}

closeModalBtn.addEventListener('click', closeModal);
modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
</script>

<script>
// Hardcoded skill tags (previously fetched from /projects/portfolio_v3/API/techstackAPI.php)
document.addEventListener('DOMContentLoaded', function() {
  const skills = [
    'Apache Server', 'CSS', 'Composer', 'Data Analytics', 'Git',
    'Graphic Design', 'HTML', 'JavaScript', 'Laravel', 'Linux',
    'MSSQL', 'MySQL', 'PHP', 'SQLite', 'Salesforce', 'Supabase',
    'System Support', 'Video Editing', 'XAMPP'
  ];
  const container = document.getElementById('skill-tags');
  skills.forEach((skill, index) => {
    const span = document.createElement('span');
    span.className = 'skill-tag';
    span.style.animationDelay = `${(index * 0.1) + 0.2}s`;
    span.textContent = skill;
    container.appendChild(span);
  });
});
</script>

<script>
// Hardcoded analytics charts slider (dummy data)
document.addEventListener('DOMContentLoaded', function() {
  const analyticsSliderTitle = document.getElementById('analyticsSliderTitle');
  const chartCanvas = document.getElementById('visitorAnalyticsChart');
  let analyticsChart = null;
  let currentSlideIndex = 0;

  const dataLabelPlugin = {
    id: 'dataLabelPlugin',
    afterDatasetsDraw(chart) {
      const ctx = chart.ctx;
      const pluginOpts = (chart.options && chart.options.plugins && chart.options.plugins.dataLabelPlugin) || {};
      if (pluginOpts.enabled === false) return;
      const color = pluginOpts.color || '#ffffff';
      const font = pluginOpts.font || '12px Arial';
      const bg = pluginOpts.background || 'rgba(0,0,0,0.65)';
      const padding = pluginOpts.padding || 6;
      const position = pluginOpts.position || 'top';

      chart.data.datasets.forEach((dataset, datasetIndex) => {
        const meta = chart.getDatasetMeta(datasetIndex);
        if (!meta || !meta.data) return;
        meta.data.forEach((element, index) => {
          const value = dataset.data[index];
          if (value === null || value === undefined) return;
          ctx.save();
          ctx.font = font;
          ctx.fillStyle = color;
          ctx.textAlign = 'center';
          const x = element.x;
          let y;
          if (position === 'top') {
            const chartTop = chart.chartArea ? chart.chartArea.top : 0;
            y = element.y - 8;
            if (y < chartTop + 6) y = chartTop + 6;
            ctx.textBaseline = 'bottom';
          } else {
            const xScale = chart.scales.x;
            if (xScale && xScale.bottom) {
              y = xScale.bottom + 14;
              const canvasBottom = chart.canvas ? chart.canvas.height : y + 30;
              if (y > canvasBottom - 8) y = canvasBottom - 8;
              ctx.textBaseline = 'top';
            } else {
              y = element.y + 14;
              ctx.textBaseline = 'top';
            }
          }
          const labelText = Math.round(value).toString();
          const metrics = ctx.measureText(labelText);
          const fontSizeMatches = /^(\d+)px/.exec(font);
          const fontSize = fontSizeMatches ? parseInt(fontSizeMatches[1], 10) : 12;
          const textWidth = metrics.width;
          const textHeight = fontSize;
          if (ctx.textBaseline === 'bottom') {
            ctx.fillStyle = bg;
            ctx.fillRect(x - (textWidth / 2) - padding, y - textHeight - padding, textWidth + (padding * 2), textHeight + (padding * 1.7));
            ctx.fillStyle = color;
            ctx.fillText(labelText, x, y - (padding * 0.3));
          } else {
            ctx.fillStyle = bg;
            ctx.fillRect(x - (textWidth / 2) - padding, y - (padding * 0.5), textWidth + (padding * 2), textHeight + (padding * 1.7));
            ctx.fillStyle = color;
            ctx.fillText(labelText, x, y + (textHeight * 0.15));
          }
          ctx.restore();
        });
      });
    }
  };

  const doughnutCalloutPlugin = {
    id: 'doughnutCalloutPlugin',
    afterDatasetsDraw(chart) {
      if (chart.config.type !== 'doughnut') return;
      const meta = chart.getDatasetMeta(0);
      const dataset = chart.data.datasets && chart.data.datasets[0];
      if (!meta || !meta.data || !dataset || !dataset.data) return;

      const ctx = chart.ctx;
      ctx.save();
      ctx.font = '12px Arial';
      ctx.fillStyle = '#ffffff';
      ctx.strokeStyle = 'rgba(255,255,255,0.35)';
      ctx.lineWidth = 1;

      meta.data.forEach((arc, i) => {
        const value = dataset.data[i];
        if (value === null || value === undefined) return;
        const label = (chart.data.labels && chart.data.labels[i]) ? chart.data.labels[i] : `Item ${i + 1}`;

        const angle = (arc.startAngle + arc.endAngle) / 2;
        const sx = arc.x + Math.cos(angle) * arc.outerRadius;
        const sy = arc.y + Math.sin(angle) * arc.outerRadius;
        const ex = arc.x + Math.cos(angle) * (arc.outerRadius + 18);
        const ey = arc.y + Math.sin(angle) * (arc.outerRadius + 18);
        const isRight = Math.cos(angle) >= 0;
        const text = `${label}: ${value}`;
        const textWidth = ctx.measureText(text).width;
        const canvasWidth = chart.canvas ? chart.canvas.width : 0;
        const canvasHeight = chart.canvas ? chart.canvas.height : 0;
        const edgePad = 6;
        let tx = ex + (isRight ? 12 : -12);
        let ty = ey;

        if (isRight) {
          tx = Math.min(tx, canvasWidth - textWidth - edgePad);
        } else {
          tx = Math.max(tx, textWidth + edgePad);
        }
        ty = Math.max(10, Math.min(ty, canvasHeight - 10));

        ctx.beginPath();
        ctx.moveTo(sx, sy);
        ctx.lineTo(ex, ey);
        ctx.stroke();

        ctx.textAlign = isRight ? 'left' : 'right';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, tx, ty);
      });

      ctx.restore();
    }
  };

  const chartSlides = [
    {
      title: 'Visitor Analytics',
      config: {
        type: 'line',
        data: {
          labels: ['Aug 2025', 'Sep 2025', 'Oct 2025', 'Nov 2025', 'Dec 2025', 'Jan 2026', 'Feb 2026'],
          datasets: [{
            label: 'Total Visits',
            data: [3, 7, 5, 12, 9, 15, 8],
            borderColor: '#6defF8',
            backgroundColor: 'rgba(109, 239, 248, 0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#6defF8',
            pointBorderColor: '#000',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#6defF8'
          }]
        },
        options: {
          plugins: {
            dataLabelPlugin: { enabled: true, position: 'top', color: '#ffffff', font: '12px Arial' },
            legend: { display: false }
          }
        }
      }
    },
    {
      title: 'Project Analytics',
      config: {
        type: 'doughnut',
        data: {
          labels: ['HTML', 'CSS', 'JavaScript', 'Git', 'MySQL', 'Laravel', 'AWS', 'Python'],
          datasets: [{
            data: [11, 10, 8, 7, 3, 2, 2, 1],
            backgroundColor: [
              'rgba(109, 239, 248, 1)',
              'rgba(109, 239, 248, 0.9)',
              'rgba(109, 239, 248, 0.8)',
              'rgba(109, 239, 248, 0.7)',
              'rgba(109, 239, 248, 0.5)',
              'rgba(109, 239, 248, 0.4)',
              'rgba(109, 239, 248, 0.3)',
              'rgba(109, 239, 248, 0.2)'
            ],
            borderColor: '#000',
            borderWidth: 2,
            offset: [15, 13, 11, 9, 7, 5, 5, 3],
            spacing: 3,
            borderRadius: 4
          }]
        },
        options: {
          plugins: {
            dataLabelPlugin: { enabled: false },
            legend: { display: false },
            tooltip: {
              enabled: false,
              backgroundColor: 'rgba(0,0,0,0.95)',
              titleColor: '#fff',
              bodyColor: '#6defF8',
              borderColor: '#6defF8',
              borderWidth: 2
            }
          },
          cutout: '60%',
          layout: { padding: { top: 25, bottom: 25, left: 25, right: 25 } }
        }
      }
    }
  ];

  const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    layout: { padding: { top: 18, bottom: 6 } },
    scales: {
      y: {
        beginAtZero: true,
        grid: { color: 'rgba(255, 255, 255, 0.05)' },
        ticks: { color: '#888', precision: 0, stepSize: 1, grace: '25%' }
      },
      x: { grid: { display: false }, ticks: { color: '#888' } }
    },
    plugins: {
      dataLabelPlugin: { enabled: true, position: 'top', color: '#ffffff', font: '12px Arial' },
      legend: { display: false },
      tooltip: {
        backgroundColor: 'rgba(0,0,0,0.8)',
        titleColor: '#fff',
        bodyColor: '#fff',
        borderColor: '#333',
        borderWidth: 1,
        displayColors: false
      }
    }
  };

  function renderChartSlide(index) {
    const slide = chartSlides[index];
    const isDoughnut = slide.config.type === 'doughnut';
    const mergedOptions = Object.assign({}, baseOptions, slide.config.options || {});
    if (isDoughnut) {
      delete mergedOptions.scales;
    }
    if (analyticsSliderTitle) analyticsSliderTitle.textContent = slide.title;
    if (analyticsChart) analyticsChart.destroy();
    analyticsChart = new Chart(chartCanvas, {
      type: slide.config.type,
      data: slide.config.data,
      plugins: isDoughnut ? [doughnutCalloutPlugin] : [dataLabelPlugin],
      options: mergedOptions
    });
  }

  renderChartSlide(currentSlideIndex);

  setInterval(function() {
    currentSlideIndex = (currentSlideIndex + 1) % chartSlides.length;
    renderChartSlide(currentSlideIndex);
  }, 10000);
});
</script>

<!-- Fixed Footer -->
<div class="site-footer">
  <footer>
    <span>Build by Mohamad Afizi. Self-hosted. Self-made. 2026 |</span>
  </footer>
</div>
</body>
</html>
<?php /**PATH /var/www/projects/portfoliov3/resources/views/index.blade.php ENDPATH**/ ?>