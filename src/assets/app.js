// ============================================
// VANTA.js Background Setup
// ============================================
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

// ============================================
// Baffle.js Text Animation
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const text = baffle(".text__glitch");
    text.set({ characters: "█▓█ ▒░/▒░ █░▒▓/ █▒▒ ▓▒▓/█ ░█▒/ ▒▓░ █<░▒ ▓/░>", speed: 50 });
    text.start();
    text.reveal(1000);
});

// ============================================
// Portfolio Navigation & Card Filtering
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-links a');
    const cards = document.querySelectorAll('.cards-grid .card');
    let isAnimating = false;
    let categoriesData = {};

    function setActiveNavLink(linkElement) {
        navLinks.forEach(link => link.classList.remove('active'));
        linkElement.classList.add('active');
    }

    const itemsPerPage = 6;

    // Fetch data from CMS API
    async function loadCategoriesData() {
        try {
            const response = await fetch('contentmanagement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=list'
            });
            const result = await response.json();
            if (result.ok && result.data) {
                return result.data;
            }
        } catch (error) {
            console.warn('Failed to load data from CMS API, using fallback:', error);
        }
        // Fallback hardcoded data if API fails
        return {
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
        { title: 'Salesforce Trailblazer', group: 'Skill-Validated Certifications', description: 'Showcasing Salesforce trails, badges, and community engagement across CRM and business cloud platforms.', techStack: [] },
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
        { title: 'Google Cloud Foundations', group: 'Course Completion', description: 'Introduction to Google Cloud Platform covering core services, cloud computing concepts, and deploying and managing applications on GCP.', techStack: [] },
        { title: 'Learning Alibaba Cloud', group: 'Course Completion', description: 'Basics of Alibaba Cloud covering services, cloud architecture, and deploying and managing applications on the platform.', techStack: [] },
        { title: 'Learning Tinkercad', group: 'Course Completion', description: '3D design and modeling with Tinkercad including creating 3D models, using basic shapes, and designing for 3D printing.', techStack: [] }
      ],
      'industry_experiences': [
        { title: 'Task 1: Role-Based Access Control for Return Order Workflow', group: 'Application Analyst & Developer (Dec 2024 ~ Present)', description: 'Implemented role-based access control restricting "Confirm" button to team leaders in return order workflow using PHP.', techStack: ['PHP'] },
        { title: 'Task 2: Customer Purchase Order Enhancement', group: 'Application Analyst & Developer (Dec 2024 ~ Present)', description: 'Enhanced PO processing with interactive IRBM validation modal, displaying multi-table joined data.', techStack: ['PHP'] },
        { title: 'Task 3: Email-to-Agency Inventory Matching Module', group: 'Application Analyst & Developer (Dec 2024 ~ Present)', description: 'Improved matching logic with agency-first display structure and searchable, sortable interface.', techStack: ['PHP'] },
        { title: 'Task 4: Galileo SFA – Web Tablet Application', group: 'Application Analyst & Developer (Dec 2024 ~ Present)', description: 'Laravel-based Sales Force Automation system with customer visit planning, inventory checks, and dynamic pricing.', techStack: ['Laravel'] },
        { title: 'Task 5: B2B SKU Maintenance Module Enhancement', group: 'Application Analyst & Developer (Dec 2024 ~ Present)', description: 'Enhanced SKU management with barcode support and data export functionality.', techStack: ['Laravel'] },
        { title: 'Task 6: B2B LHM Customer PO Processing', group: 'Application Analyst & Developer (Dec 2024 ~ Present)', description: 'Automated PO workflow with multi-format support, UOM conversions, and data validation.', techStack: ['PHP'] },
        { title: 'Task 7: Automated Daily VM Backup', group: 'Application Analyst & Developer (Dec 2024 ~ Present)', description: 'Automated Ubuntu VM backup routine using Windows batch scripts with compression and cleanup.', techStack: ['Windows Batch', 'VirtualBox'] },
        { title: 'Task 8: Customer Data Update Logic Enhancement', group: 'Application Analyst & Developer (Dec 2024 ~ Present)', description: 'Improved update logic with staged processing and audit trails to maintain data integrity.', techStack: ['Laravel'] },
        { title: 'Task 9: B2B LHS Purchase Order Processing', group: 'Application Analyst & Developer (Dec 2024 ~ Present)', description: 'Multi-retailer PO format support with file uploads, data transformation, and maintenance pages.', techStack: ['PHP'] },
        { title: 'Task 10: Daily CN Return Management Module', group: 'Application Analyst & Developer (Dec 2024 ~ Present)', description: 'Centralized module for CN return records with filtering, pagination, and bulk export capabilities.', techStack: ['PHP'] }
      ]
    };
    }

    // Initialize the UI with loaded data
    async function initPortfolioUI() {
        categoriesData = await loadCategoriesData();

        const categoryDescriptions = {
          'projects': 'A range of projects across independent builds, technical experiments, and guided learning — showcasing how I think, solve, and execute.',
          'milestones': 'Certifications, courses, and structured learning milestones that reflect my commitment to continuous improvement',
          'industry_experiences': 'Real-world contributions from my time in the industry — practical tasks, problem-solving, and hands-on experience across different roles and environments.'
    };

    const categoryGroups = {
      'projects': ['Independent Projects', 'Technical Experiments', 'Guided Projects', 'Academic Projects'],
      'milestones': ['Learning Paths', 'Skill-Validated Certifications', 'Course Completion'],
      'industry_experiences': ['Application Analyst & Developer (Dec 2024 ~ Present)']
    };

    const activeGroupPerCategory = {};
    const pagesState = {};

    function updateGroupingButtons(category) {
      const groupFilter = document.getElementById('groupingFilter');
      if (!groupFilter) return;
      groupFilter.innerHTML = '';
      const groups = categoryGroups[category] || [];
      const activeGroup = activeGroupPerCategory[category];
      if (groups.length === 0) { groupFilter.style.display = 'none'; return; }
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
    }

    // Start loading and initialization
    initPortfolioUI();
});

// ============================================
// Modal Functionality
// ============================================
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

// ============================================
// Skill Tags Rendering (from CMS JSON)
// ============================================
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('skill-tags');
  if(!container) return;

  const fallbackSkills = [
    'Apache Server', 'CSS', 'Composer', 'Data Analytics', 'Git',
    'Graphic Design', 'HTML', 'JavaScript', 'Laravel', 'Linux',
    'MSSQL', 'MySQL', 'PHP', 'SQLite', 'Salesforce', 'Supabase',
    'System Support', 'Video Editing', 'XAMPP'
  ];

  // Try to fetch CMS list to get top-level tech_stack
  fetch('contentmanagement.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=list'
  })
    .then(r => r.json())
    .then(res => {
      const techStack = res && res.data && Array.isArray(res.data.tech_stack)
        ? res.data.tech_stack
        : null;

      let skills = (techStack && techStack.length) ? techStack : fallbackSkills;

      // Always render alphabetically (A → Z)
      skills = skills
        .filter(s => typeof s === 'string' && s.trim() !== '')
        .map(s => s.trim())
        .sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' }));

      // Clear and render
      container.innerHTML = '';
      skills.forEach((skill, index) => {
        const span = document.createElement('span');
        span.className = 'skill-tag';
        span.style.animationDelay = `${(index * 0.1) + 0.2}s`;
        span.textContent = skill;
        container.appendChild(span);
      });
    })
    .catch(() => {
      // Fallback if CMS is unreachable
      container.innerHTML = '';
      fallbackSkills.forEach((skill, index) => {
        const span = document.createElement('span');
        span.className = 'skill-tag';
        span.style.animationDelay = `${(index * 0.1) + 0.2}s`;
        span.textContent = skill;
        container.appendChild(span);
      });
    });
});

// ============================================
// Analytics Charts with Custom Plugins
// ============================================
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
