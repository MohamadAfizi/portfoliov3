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
    const cardsWrapper = document.querySelector('.cards-wrapper');
    const industryTimeline = document.getElementById('industryTimeline');
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
          groupFilter.querySelectorAll('.group-btn').forEach(groupButton => {
            groupButton.classList.toggle(
              'active',
              groupButton.dataset.group === activeGroupPerCategory[category]
            );
          });
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
          const itemNumber = (page * itemsPerPage) + index + 1;
          card.dataset.terminalLabel = `[${String(category).toUpperCase()}_${String(itemNumber).padStart(2, '0')}]`;
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
            const actions = Array.isArray(item.actions) ? item.actions : [];

            actions.forEach(action => {
              const label = typeof action.label === 'string' ? action.label.toLowerCase() : '';
              const isExternal = action.type === 'external';
              const isModal = action.type === 'modal';
              const hasUrl = isExternal && typeof action.url === 'string' && /^https?:\/\//i.test(action.url);
              const hasSource = isModal
                && typeof action.source === 'string'
                && /^content\/readmes\/[a-z0-9/_-]+\.md$/i.test(action.source);
              const isEnabled = hasUrl || hasSource;
              const elementName = isEnabled ? (isModal ? 'button' : 'a') : 'span';
              const control = document.createElement(elementName);
              control.className = isEnabled ? 'card-link' : 'card-link card-link-disabled';
              control.textContent = label;

              if (hasUrl) {
                control.href = action.url;
                control.setAttribute('aria-label', `Open ${label} for ${item.title}`);
                control.target = '_blank';
                control.rel = 'noopener noreferrer';
              } else if (hasSource) {
                control.type = 'button';
                control.setAttribute('aria-label', `Open ${label} for ${item.title}`);
                control.addEventListener('click', () => {
                  openReadmeModal(item.title, action.source, control);
                });
              } else {
                control.setAttribute('aria-disabled', 'true');
                control.setAttribute('aria-label', `${label} is not configured for ${item.title}`);
                control.title = `${label} is not configured`;
              }

              cardLinks.appendChild(control);
            });

            cardLinks.hidden = actions.length === 0;
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

    function renderIndustryTimeline(experiences, achievements) {
      if (!industryTimeline) return;
      industryTimeline.innerHTML = '';

      if (achievements.length > 0) {
        const achievementSection = document.createElement('section');
        achievementSection.className = 'industry-achievements';
        achievementSection.setAttribute('aria-labelledby', 'industryAchievementsTitle');

        const achievementCommand = document.createElement('p');
        achievementCommand.className = 'industry-command';
        achievementCommand.textContent = '$ career-impact --selected';
        achievementSection.appendChild(achievementCommand);

        const achievementHeading = document.createElement('h3');
        achievementHeading.id = 'industryAchievementsTitle';
        achievementHeading.textContent = 'Key achievements';
        achievementSection.appendChild(achievementHeading);

        const achievementList = document.createElement('ol');
        achievementList.className = 'achievement-list';

        achievements.forEach((achievement, index) => {
          const item = document.createElement('li');
          item.className = 'achievement-item';
          item.style.setProperty('--item-index', index);

          const itemNumber = document.createElement('span');
          itemNumber.className = 'achievement-number';
          itemNumber.textContent = String(index + 1).padStart(2, '0');
          itemNumber.setAttribute('aria-hidden', 'true');
          item.appendChild(itemNumber);

          const itemContent = document.createElement('div');
          const itemTitle = document.createElement('h4');
          itemTitle.textContent = achievement.title || '';
          itemContent.appendChild(itemTitle);

          if (achievement.summary) {
            const itemSummary = document.createElement('p');
            itemSummary.textContent = achievement.summary;
            itemContent.appendChild(itemSummary);
          }

          item.appendChild(itemContent);
          achievementList.appendChild(item);
        });

        achievementSection.appendChild(achievementList);
        industryTimeline.appendChild(achievementSection);
      }

      const roles = document.createElement('div');
      roles.className = 'timeline-roles';
      roles.setAttribute('aria-label', 'Employment timeline');

      experiences.forEach((experience, index) => {
        const role = document.createElement('article');
        role.className = 'timeline-role';
        role.style.setProperty('--item-index', achievements.length + index);
        if (experience.current === true) role.classList.add('is-current');

        const period = document.createElement('p');
        period.className = 'timeline-period';
        period.textContent = [experience.from, experience.to].filter(Boolean).join(' — ');
        role.appendChild(period);

        const heading = document.createElement('h3');
        heading.className = 'timeline-role-title';
        heading.textContent = experience.role || '';
        role.appendChild(heading);

        if (experience.scope) {
          const scope = document.createElement('p');
          scope.className = 'timeline-scope';
          const scopeLabel = document.createElement('span');
          scopeLabel.textContent = 'scope: ';
          scope.appendChild(scopeLabel);
          scope.appendChild(document.createTextNode(experience.scope));
          role.appendChild(scope);
        }

        roles.appendChild(role);
      });

      industryTimeline.appendChild(roles);
    }

    function updateCategoryPresentation(category) {
      const descElement = document.getElementById('categoryDescription');
      if (descElement) {
        descElement.classList.remove('active');
        setTimeout(() => {
          descElement.textContent = categoryDescriptions[category] || '';
          descElement.classList.add('active');
        }, 150);
      }
      updateGroupingButtons(category);
    }

    function animateCards(category) {
      if (category === 'industry_experiences') {
        if (cardsWrapper) cardsWrapper.hidden = true;
        if (industryTimeline) {
          industryTimeline.hidden = false;
          const achievements = Array.isArray(categoryConfig[category]?.keyAchievements)
            ? categoryConfig[category].keyAchievements
            : [];
          renderIndustryTimeline(categoriesData[category] || [], achievements);
          industryTimeline.classList.remove('animate');
          void industryTimeline.offsetWidth;
          industryTimeline.classList.add('animate');
        }
        return;
      }

      if (cardsWrapper) cardsWrapper.hidden = false;
      if (industryTimeline) {
        industryTimeline.hidden = true;
        industryTimeline.classList.remove('animate');
      }

      cards.forEach((card, index) => {
        setTimeout(() => { card.classList.remove('animate'); card.classList.add('exit'); }, index * 80);
      });
      setTimeout(() => {
        updateCardContent(category);
        cards.forEach(card => { card.classList.remove('animate', 'exit'); void card.offsetWidth; });
        cards.forEach((card, index) => {
          setTimeout(() => { card.classList.add('animate'); }, index * 100);
        });
      }, 600);
    }

    navLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        setActiveNavLink(e.currentTarget);
        const category = e.currentTarget.dataset.category;
        activeGroupPerCategory[category] = null;
        pagesState[category] = 0;
        updateCategoryPresentation(category);
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
    updateCategoryPresentation('projects');
    animateCards('projects');
    const initialLink = document.querySelector('.nav-links a[data-category="projects"]') || navLinks[0];
    if (initialLink) setActiveNavLink(initialLink);
    }

    // Start loading and initialization
    initPortfolioUI();
});

// ============================================
// Local Markdown README Modal
// ============================================
const readmeModal = document.getElementById('readmeModal');
const readmeModalTitle = document.getElementById('readmeModalTitle');
const readmeModalBody = document.getElementById('readmeModalBody');
const closeReadmeModalBtn = document.getElementById('closeReadmeModalBtn');
let readmeReturnFocus = null;

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function renderInlineMarkdown(value) {
  const codeTokens = [];
  const tokenized = String(value).replace(/`([^`]+)`/g, (_, code) => {
    const token = `\u0000CODE${codeTokens.length}\u0000`;
    codeTokens.push(`<code>${escapeHtml(code)}</code>`);
    return token;
  });

  let html = escapeHtml(tokenized);
  html = html.replace(
    /!\[([^\]]*)\]\(((?:https?:\/\/|\/?media\/)[^\s)"'<>]+)\)/gi,
    '<img src="$2" alt="$1" loading="lazy">'
  );
  html = html.replace(
    /\[([^\]]+)\]\(((?:https?:\/\/|\/)[^\s)"'<>]+)\)/gi,
    '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>'
  );
  html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
  html = html.replace(/__([^_]+)__/g, '<strong>$1</strong>');
  html = html.replace(/(^|[^*])\*([^*]+)\*/g, '$1<em>$2</em>');

  codeTokens.forEach((code, index) => {
    html = html.replace(`\u0000CODE${index}\u0000`, code);
  });

  return html;
}

function renderMarkdown(markdown) {
  const lines = String(markdown).replace(/\r\n?/g, '\n').split('\n');
  const output = [];
  let paragraph = [];
  let listType = null;
  let inCodeBlock = false;
  let codeLines = [];

  const flushParagraph = () => {
    if (paragraph.length === 0) return;
    output.push(`<p>${renderInlineMarkdown(paragraph.join(' '))}</p>`);
    paragraph = [];
  };

  const closeList = () => {
    if (!listType) return;
    output.push(`</${listType}>`);
    listType = null;
  };

  lines.forEach(line => {
    if (/^```/.test(line)) {
      flushParagraph();
      closeList();
      if (inCodeBlock) {
        output.push(`<pre><code>${escapeHtml(codeLines.join('\n'))}</code></pre>`);
        codeLines = [];
      }
      inCodeBlock = !inCodeBlock;
      return;
    }

    if (inCodeBlock) {
      codeLines.push(line);
      return;
    }

    if (line.trim() === '') {
      flushParagraph();
      closeList();
      return;
    }

    const heading = line.match(/^(#{1,6})\s+(.+)$/);
    if (heading) {
      flushParagraph();
      closeList();
      const level = heading[1].length;
      output.push(`<h${level}>${renderInlineMarkdown(heading[2])}</h${level}>`);
      return;
    }

    if (/^\s*(---+|___+)\s*$/.test(line)) {
      flushParagraph();
      closeList();
      output.push('<hr>');
      return;
    }

    const unorderedItem = line.match(/^\s*[-*+]\s+(.+)$/);
    const orderedItem = line.match(/^\s*\d+\.\s+(.+)$/);
    if (unorderedItem || orderedItem) {
      flushParagraph();
      const nextListType = unorderedItem ? 'ul' : 'ol';
      if (listType !== nextListType) {
        closeList();
        listType = nextListType;
        output.push(`<${listType}>`);
      }
      output.push(`<li>${renderInlineMarkdown((unorderedItem || orderedItem)[1])}</li>`);
      return;
    }

    const quote = line.match(/^>\s?(.*)$/);
    if (quote) {
      flushParagraph();
      closeList();
      output.push(`<blockquote>${renderInlineMarkdown(quote[1])}</blockquote>`);
      return;
    }

    paragraph.push(line.trim());
  });

  if (inCodeBlock) {
    output.push(`<pre><code>${escapeHtml(codeLines.join('\n'))}</code></pre>`);
  }
  flushParagraph();
  closeList();
  return output.join('');
}

async function openReadmeModal(title, source, trigger) {
  if (!readmeModal || !readmeModalTitle || !readmeModalBody || !closeReadmeModalBtn) return;
  if (!/^content\/readmes\/[a-z0-9/_-]+\.md$/i.test(source)) return;

  readmeReturnFocus = trigger || document.activeElement;
  readmeModalTitle.textContent = title;
  readmeModalBody.innerHTML = '<p class="readme-status">Loading README…</p>';
  readmeModal.classList.add('visible');
  readmeModal.setAttribute('aria-hidden', 'false');
  document.body.classList.add('modal-open');
  closeReadmeModalBtn.focus();

  try {
    const response = await fetch(source, { headers: { Accept: 'text/markdown, text/plain' } });
    if (!response.ok) throw new Error(`README request failed with HTTP ${response.status}`);
    readmeModalBody.innerHTML = renderMarkdown(await response.text());
  } catch (error) {
    readmeModalBody.innerHTML = '<p class="readme-status readme-status-error">README is temporarily unavailable.</p>';
  }
}

function closeReadmeModal() {
  if (!readmeModal) return;
  readmeModal.classList.remove('visible');
  readmeModal.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('modal-open');
  if (readmeReturnFocus && typeof readmeReturnFocus.focus === 'function') readmeReturnFocus.focus();
  readmeReturnFocus = null;
}

if (closeReadmeModalBtn) closeReadmeModalBtn.addEventListener('click', closeReadmeModal);
if (readmeModal) {
  readmeModal.addEventListener('click', event => {
    if (event.target === readmeModal) closeReadmeModal();
  });
}
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && readmeModal && readmeModal.classList.contains('visible')) {
    closeReadmeModal();
  }
});

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
// Visitor Analytics & GitHub Contributions
// ============================================
document.addEventListener('DOMContentLoaded', function() {
  const analyticsSliderTitle = document.getElementById('analyticsSliderTitle');
  const chartCanvas = document.getElementById('visitorAnalyticsChart');
  const githubGraph = document.getElementById('githubContributionGraph');
  if (!chartCanvas || !githubGraph) return;

  const analyticsData = portfolioData.analytics || {};
  const visitorData = analyticsData.visitors || { labels: [], values: [] };
  const githubConfig = portfolioData.github || {};
  let analyticsChart = null;
  let currentSlideIndex = 0;
  let githubRequest = null;

  if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "'JetBrains Mono', 'Cascadia Code', 'Fira Code', Consolas, monospace";
  }

  const dataLabelPlugin = {
    id: 'dataLabelPlugin',
    afterDatasetsDraw(chart) {
      const ctx = chart.ctx;
      const pluginOpts = (chart.options && chart.options.plugins && chart.options.plugins.dataLabelPlugin) || {};
      if (pluginOpts.enabled === false) return;
      const color = pluginOpts.color || '#ffffff';
      const font = pluginOpts.font || "12px 'JetBrains Mono', monospace";
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

  const visitorChartConfig = {
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
        dataLabelPlugin: { enabled: true, position: 'top', color: '#ffffff', font: "12px 'JetBrains Mono', monospace" },
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
    }
  };

  function showGithubFallback() {
    githubGraph.innerHTML = '';

    const status = document.createElement('p');
    status.className = 'github-status github-status-error';
    status.textContent = githubConfig.unavailable_text || '';
    githubGraph.appendChild(status);

    if (githubConfig.profile_url) {
      const link = document.createElement('a');
      link.className = 'github-profile-link';
      link.href = githubConfig.profile_url;
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      link.textContent = githubConfig.view_profile_label || githubConfig.profile_url;
      githubGraph.appendChild(link);
    }
  }

  function contributionText(day) {
    const count = Number(day.count) || 0;
    const noun = count === 1
      ? (githubConfig.contribution_singular || 'contribution')
      : (githubConfig.contribution_plural || 'contributions');
    const date = new Date(`${day.date}T00:00:00Z`).toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      timeZone: 'UTC'
    });
    return `${count} ${noun} ${githubConfig.on_label || 'on'} ${date}`;
  }

  function renderGithubCalendar(data) {
    const weeks = Array.isArray(data.weeks) ? data.weeks : [];
    if (weeks.length === 0) {
      showGithubFallback();
      return;
    }

    githubGraph.innerHTML = '';

    const summary = document.createElement('div');
    summary.className = 'github-summary';

    const total = document.createElement('span');
    total.className = 'github-total';
    total.textContent = `${Number(data.totalContributions) || 0} ${githubConfig.subtitle || ''}`.trim();
    summary.appendChild(total);

    const profileLink = document.createElement('a');
    profileLink.className = 'github-profile-link';
    profileLink.href = data.profileUrl || githubConfig.profile_url || '#';
    profileLink.target = '_blank';
    profileLink.rel = 'noopener noreferrer';
    profileLink.textContent = githubConfig.view_profile_label || data.username || '';
    summary.appendChild(profileLink);
    githubGraph.appendChild(summary);

    const scroller = document.createElement('div');
    scroller.className = 'github-calendar-scroll';

    const calendar = document.createElement('div');
    calendar.className = 'github-calendar';

    const months = document.createElement('div');
    months.className = 'github-months';
    months.style.gridTemplateColumns = `repeat(${weeks.length}, var(--github-cell-size))`;

    let previousMonth = '';
    weeks.forEach((week, weekIndex) => {
      const firstDay = Array.isArray(week.days) ? week.days[0] : null;
      if (!firstDay || !firstDay.date) return;
      const date = new Date(`${firstDay.date}T00:00:00Z`);
      const monthKey = `${date.getUTCFullYear()}-${date.getUTCMonth()}`;
      if (monthKey === previousMonth || date.getUTCDate() > 7) return;

      const label = document.createElement('span');
      label.textContent = date.toLocaleDateString(undefined, { month: 'short', timeZone: 'UTC' });
      label.style.gridColumn = `${weekIndex + 1} / span 4`;
      months.appendChild(label);
      previousMonth = monthKey;
    });
    calendar.appendChild(months);

    const weekGrid = document.createElement('div');
    weekGrid.className = 'github-weeks';

    weeks.forEach(week => {
      const weekColumn = document.createElement('div');
      weekColumn.className = 'github-week';

      (Array.isArray(week.days) ? week.days : []).forEach(day => {
        const cell = document.createElement('span');
        const level = String(day.level || 'NONE').toLowerCase().replaceAll('_', '-');
        const label = contributionText(day);
        cell.className = 'github-day';
        cell.dataset.level = level;
        cell.title = label;
        cell.setAttribute('role', 'img');
        cell.setAttribute('aria-label', label);
        weekColumn.appendChild(cell);
      });

      weekGrid.appendChild(weekColumn);
    });

    calendar.appendChild(weekGrid);
    scroller.appendChild(calendar);
    githubGraph.appendChild(scroller);

    const legend = document.createElement('div');
    legend.className = 'github-legend';

    const less = document.createElement('span');
    less.textContent = githubConfig.less_label || '';
    legend.appendChild(less);

    ['none', 'first-quartile', 'second-quartile', 'third-quartile', 'fourth-quartile'].forEach(level => {
      const cell = document.createElement('span');
      cell.className = 'github-day';
      cell.dataset.level = level;
      cell.setAttribute('aria-hidden', 'true');
      legend.appendChild(cell);
    });

    const more = document.createElement('span');
    more.textContent = githubConfig.more_label || '';
    legend.appendChild(more);
    githubGraph.appendChild(legend);
  }

  function loadGithubContributions() {
    if (githubRequest) return githubRequest;

    githubGraph.innerHTML = '';
    const loading = document.createElement('p');
    loading.className = 'github-status';
    loading.textContent = githubConfig.loading_text || '';
    githubGraph.appendChild(loading);

    githubRequest = fetch('api/github-contributions.php', {
      method: 'GET',
      headers: { Accept: 'application/json' },
      credentials: 'same-origin'
    })
      .then(response => {
        if (!response.ok) throw new Error(`GitHub endpoint returned ${response.status}`);
        return response.json();
      })
      .then(result => {
        if (!result || result.ok !== true || !result.data) throw new Error('GitHub data was unavailable');
        renderGithubCalendar(result.data);
      })
      .catch(() => {
        showGithubFallback();
      });

    return githubRequest;
  }

  function renderVisitorAnalytics() {
    githubGraph.hidden = true;
    chartCanvas.hidden = false;
    if (analyticsSliderTitle) analyticsSliderTitle.textContent = uiText.visitor_analytics_title || '';
    if (analyticsChart) analyticsChart.destroy();
    if (typeof Chart !== 'undefined') {
      analyticsChart = new Chart(chartCanvas, {
        ...visitorChartConfig,
        plugins: [dataLabelPlugin]
      });
    }
  }

  function renderGithubContributions() {
    chartCanvas.hidden = true;
    githubGraph.hidden = false;
    if (analyticsSliderTitle) analyticsSliderTitle.textContent = githubConfig.title || '';
    if (analyticsChart) {
      analyticsChart.destroy();
      analyticsChart = null;
    }
    loadGithubContributions();
  }

  function renderAnalyticsSlide(index) {
    if (index === 1) {
      renderGithubContributions();
    } else {
      renderVisitorAnalytics();
    }
  }

  renderAnalyticsSlide(currentSlideIndex);

  setInterval(function() {
    currentSlideIndex = (currentSlideIndex + 1) % 2;
    renderAnalyticsSlide(currentSlideIndex);
  }, 10000);
});
