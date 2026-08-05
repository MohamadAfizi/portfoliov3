function readPortfolioData() {
  const dataElement = document.getElementById('portfolio-data');
  if (!dataElement) return {};

  try {
    return JSON.parse(dataElement.textContent || '{}');
  } catch (error) {
    console.warn('Unable to read portfolio data.', error);
    return {};
  }
}

const portfolioData = readPortfolioData();
const uiText = portfolioData.ui || {};

// Track asynchronously so IP-location lookup never delays the visible page.
document.addEventListener('DOMContentLoaded', function() {
  fetch('api/track-visitor.php', {
    method: 'POST',
    cache: 'no-store',
    keepalive: true
  }).catch(() => {
    // Tracking is optional and must never interrupt the portfolio UI.
  });
});

// ============================================
// VANTA.js Background Setup
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    if (typeof VANTA === 'undefined' || !document.getElementById('vanta-bg')) return;
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
    if (typeof baffle === 'undefined' || !document.querySelector('.text__glitch')) return;
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

    const categoryConfig = portfolioData.categories || {};

    // Content is loaded once by PHP from data/content.json and embedded as JSON.
    async function loadCategoriesData() {
      return Object.fromEntries(
        Object.entries(categoryConfig).map(([key, category]) => [
          key,
          Array.isArray(category.items) ? category.items : []
        ])
      );
    }

    // Initialize the UI with loaded data
    async function initPortfolioUI() {
        categoriesData = await loadCategoriesData();

    const categoryDescriptions = Object.fromEntries(
      Object.entries(categoryConfig).map(([key, category]) => [key, category.description || ''])
    );

    const categoryGroups = Object.fromEntries(
      Object.entries(categoryConfig).map(([key, category]) => [
        key,
        Array.isArray(category.groups) ? category.groups : []
      ])
    );

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
            docLink.textContent = uiText.details_label || '';
            docLink.onclick = () => openModal(item.title, item.description);
            cardLinks.appendChild(docLink);
            const demoLink = document.createElement('a');
            demoLink.href = '#';
            demoLink.className = 'card-link';
            demoLink.textContent = uiText.demo_label || '';
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
      if (pageInfo) pageInfo.textContent = totalPages > 1
        ? `${uiText.page_label || ''} ${page + 1} / ${totalPages}`
        : '';
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
    const initialLink = document.querySelector('.nav-links a[data-category="projects"]') || navLinks[0];
    if (initialLink) setActiveNavLink(initialLink);
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
    if (!modal || !modalTitle || !modalBody) return;
    modalTitle.textContent = title;
    modalBody.textContent = content;
    modal.classList.add('visible');
    modal.setAttribute('aria-hidden', 'false');
}

function closeModal() {
    if (!modal) return;
    modal.classList.remove('visible');
    modal.setAttribute('aria-hidden', 'true');
}

if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
if (modal) modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

// ============================================
// Skill Tags Rendering (from CMS JSON)
// ============================================
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('skill-tags');
  if(!container) return;

  const skills = (Array.isArray(portfolioData.techStack) ? portfolioData.techStack : [])
    .filter(skill => typeof skill === 'string' && skill.trim() !== '')
    .map(skill => skill.trim())
    .sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' }));

  container.innerHTML = '';
  skills.forEach((skill, index) => {
    const span = document.createElement('span');
    span.className = 'skill-tag';
    span.style.animationDelay = `${(index * 0.1) + 0.2}s`;
    span.textContent = skill;
    container.appendChild(span);
  });
});

// ============================================
// Analytics Charts with Custom Plugins
// ============================================
document.addEventListener('DOMContentLoaded', function() {
  const analyticsSliderTitle = document.getElementById('analyticsSliderTitle');
  const chartCanvas = document.getElementById('visitorAnalyticsChart');
  if (!chartCanvas || typeof Chart === 'undefined') return;

  const analyticsData = portfolioData.analytics || {};
  const visitorData = analyticsData.visitors || { labels: [], values: [] };
  const projectData = analyticsData.projects || { labels: [], values: [] };
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
      title: uiText.visitor_analytics_title || '',
      config: {
        type: 'line',
        data: {
          labels: visitorData.labels || [],
          datasets: [{
            label: uiText.total_visits_label || '',
            data: visitorData.values || [],
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
      title: uiText.project_analytics_title || '',
      config: {
        type: 'doughnut',
        data: {
          labels: projectData.labels || [],
          datasets: [{
            data: projectData.values || [],
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
