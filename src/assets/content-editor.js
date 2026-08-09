(() => {
  'use strict';

  const clone = (value) => JSON.parse(JSON.stringify(value));
  const state = clone(window.CONTENT_EDITOR_DATA || {});
  const logs = clone(Array.isArray(window.CONTENT_EDITOR_LOGS) ? window.CONTENT_EDITOR_LOGS : []);
  const dirtySections = new Set();
  const pendingOperations = {
    site: new Set(),
    tech_stack: new Set(),
    projects: new Set(),
    milestones: new Set(),
    industry_experiences: new Set(),
  };
  const alphabeticalSort = new Intl.Collator(undefined, { sensitivity: 'base', numeric: true });
  const sectionLabels = {
    site: 'Site & Profile',
    tech_stack: 'Tech Stack',
    projects: 'Projects',
    milestones: 'Milestones',
    industry_experiences: 'Industry Experience',
  };
  let activeDialogSection = '';
  const activePortfolioGroup = { projects: '', milestones: '' };
  let activeExperienceTab = 'achievements';
  let controlId = 0;
  let lastSavedMessage = '';

  state.site = objectValue(state.site);
  state.navigation = objectValue(state.navigation);
  state.ui = objectValue(state.ui);
  state.tech_stack = cleanTags(state.tech_stack);
  state.projects = arrayValue(state.projects);
  state.milestones = arrayValue(state.milestones);
  state.industry_experiences = objectValue(state.industry_experiences);
  state.industry_experiences.keyAchievements = arrayValue(state.industry_experiences.keyAchievements);
  state.industry_experiences.roles = arrayValue(state.industry_experiences.roles);

  const savedMetrics = {
    site: 0,
    tech_stack: sectionMetric('tech_stack'),
    projects: sectionMetric('projects'),
    milestones: sectionMetric('milestones'),
    industry_experiences: sectionMetric('industry_experiences'),
  };

  function arrayValue(value) {
    return Array.isArray(value) ? value : [];
  }

  function objectValue(value) {
    return value && typeof value === 'object' && !Array.isArray(value) ? value : {};
  }

  function createElement(tag, attributes = {}, children = []) {
    const element = document.createElement(tag);
    Object.entries(attributes).forEach(([key, value]) => {
      if (key === 'className') element.className = value;
      else if (key === 'text') element.textContent = value;
      else if (key === 'dataset') Object.assign(element.dataset, value);
      else if (key.startsWith('on') && typeof value === 'function') element.addEventListener(key.slice(2).toLowerCase(), value);
      else if (value !== undefined && value !== null) element[key] = value;
    });
    children.filter(Boolean).forEach((child) => element.append(child));
    return element;
  }

  function makeInput(value = '', placeholder = '', onInput = null) {
    const input = createElement('input', { value: String(value ?? ''), placeholder });
    if (onInput) input.addEventListener('input', () => onInput(input.value));
    return input;
  }

  function makeTextarea(value = '', className = '', onInput = null) {
    const textarea = createElement('textarea', { value: String(value ?? ''), className });
    if (onInput) textarea.addEventListener('input', () => onInput(textarea.value));
    return textarea;
  }

  function makeSelect(value, options, onChange) {
    const select = createElement('select');
    const values = options.map((option) => option.value);
    if (value && !values.includes(value)) {
      select.append(createElement('option', { value, text: value }));
    }
    options.forEach((option) => select.append(createElement('option', option)));
    select.value = value || options[0]?.value || '';
    select.addEventListener('change', () => onChange(select.value));
    return select;
  }

  function makeField(labelText, control, className = '', meta = null) {
    if (!control.id) control.id = `editor-control-${++controlId}`;
    const label = createElement('label', { htmlFor: control.id, text: labelText });
    if (meta) label.append(meta);
    return createElement('div', { className: `field ${className}`.trim() }, [label, control]);
  }

  function makeButton(text, className = 'button', title = '') {
    return createElement('button', { type: 'button', className, text, title });
  }

  function makeIcon(name) {
    const paths = {
      up: 'M6 14l6-6 6 6',
      down: 'M6 10l6 6 6-6',
      delete: 'M5 7h14M9 7V4h6v3m2 0-1 13H8L7 7m3 4v5m4-5v5',
    };
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('aria-hidden', 'true');
    svg.setAttribute('focusable', 'false');
    path.setAttribute('d', paths[name]);
    path.setAttribute('fill', 'none');
    path.setAttribute('stroke', 'currentColor');
    path.setAttribute('stroke-width', '1.8');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('stroke-linejoin', 'round');
    svg.append(path);
    return svg;
  }

  function toneFor(value) {
    const source = String(value || 'record');
    let hash = 0;
    for (let index = 0; index < source.length; index += 1) hash = ((hash << 5) - hash) + source.charCodeAt(index);
    return String(Math.abs(hash) % 6);
  }

  function makeGroupTab(label, count, tone, isActive, onClick) {
    const button = createElement('button', {
      type: 'button',
      className: `group-tab${isActive ? ' is-active' : ''}`,
      dataset: { tone },
    }, [
      createElement('span', { text: label }),
      createElement('strong', { text: count }),
    ]);
    button.setAttribute('role', 'tab');
    button.setAttribute('aria-selected', String(isActive));
    button.addEventListener('click', onClick);
    return button;
  }

  function emptyState(message) {
    return createElement('div', { className: 'empty-state', text: message });
  }

  function setAtPath(path, value) {
    const parts = path.split('.');
    let target = state;
    parts.slice(0, -1).forEach((part) => {
      target[part] = objectValue(target[part]);
      target = target[part];
    });
    target[parts.at(-1)] = value;
  }

  function setText(id, value) {
    const element = document.getElementById(id);
    if (element) element.textContent = String(value);
  }

  function sectionMetric(section) {
    if (section === 'tech_stack') return arrayValue(state.tech_stack).length;
    if (section === 'projects') return arrayValue(state.projects).length;
    if (section === 'milestones') return arrayValue(state.milestones).length;
    if (section === 'industry_experiences') {
      const industry = objectValue(state.industry_experiences);
      return arrayValue(industry.keyAchievements).length + arrayValue(industry.roles).length;
    }
    return 0;
  }

  function updateCounts() {
    const industry = state.industry_experiences;
    const achievements = industry.keyAchievements.length;
    const roles = industry.roles.length;
    setText('techCount', state.tech_stack.length);
    setText('projectsCount', state.projects.length);
    setText('milestonesCount', state.milestones.length);
    setText('experienceCount', achievements + roles);
    setText('achievementsCount', achievements);
    setText('rolesCount', roles);
    setText('achievementTabCount', achievements);
    setText('roleTabCount', roles);
    setText('navTechCount', state.tech_stack.length);
    setText('navProjectsCount', state.projects.length);
    setText('navMilestonesCount', state.milestones.length);
    setText('navExperienceCount', achievements + roles);
  }

  function updateOverview() {
    const overview = document.querySelector('.save-overview');
    const label = document.getElementById('saveOverview');
    const count = dirtySections.size;
    overview?.classList.toggle('is-dirty', count > 0);
    if (!label) return;
    if (count > 0) {
      label.textContent = `${count} unsaved ${count === 1 ? 'section' : 'sections'}`;
    } else {
      label.textContent = lastSavedMessage || 'All changes saved';
    }
  }

  function markDirty(section, operation = 'edit') {
    dirtySections.add(section);
    pendingOperations[section]?.add(operation);
    const card = document.querySelector(`[data-section-card="${section}"]`);
    const status = document.querySelector(`[data-status="${section}"]`);
    const saveButton = document.querySelector(`[data-save-section="${section}"]`);
    card?.classList.add('is-dirty');
    if (status) {
      status.textContent = 'Unsaved changes';
      status.className = 'section-status is-dirty';
    }
    if (saveButton) saveButton.disabled = false;
    updateOverview();
  }

  function markClean(section, message) {
    dirtySections.delete(section);
    pendingOperations[section]?.clear();
    const card = document.querySelector(`[data-section-card="${section}"]`);
    const status = document.querySelector(`[data-status="${section}"]`);
    const saveButton = document.querySelector(`[data-save-section="${section}"]`);
    card?.classList.remove('is-dirty');
    if (status) {
      status.textContent = message;
      status.className = 'section-status is-success';
    }
    if (saveButton) saveButton.disabled = true;
    updateOverview();
  }

  function showError(section, message) {
    const status = document.querySelector(`[data-status="${section}"]`);
    if (status) {
      status.textContent = message;
      status.className = 'section-status is-error';
    }
  }

  function showToast(message, isError = false) {
    const region = document.getElementById('toastRegion');
    if (!region) return;
    const toast = createElement('div', { className: `toast${isError ? ' is-error' : ''}`, text: message });
    region.append(toast);
    window.setTimeout(() => toast.remove(), 4200);
  }

  function cleanTags(tags) {
    const cleaned = [];
    arrayValue(tags).forEach((tag) => {
      const value = String(tag ?? '').trim();
      if (value && !cleaned.some((existing) => alphabeticalSort.compare(existing, value) === 0)) {
        cleaned.push(value);
      }
    });
    return cleaned.sort((first, second) => alphabeticalSort.compare(first, second));
  }

  function prepareSection(section) {
    if (section === 'tech_stack') {
      state.tech_stack = cleanTags(state.tech_stack);
      renderTechStack();
    }
    if (section === 'projects' || section === 'milestones') {
      state[section].forEach((item) => {
        if (Array.isArray(item.techStack)) item.techStack = cleanTags(item.techStack);
      });
      renderPortfolioSection(section);
    }
  }

  function successMessage(section, previousMetric) {
    const added = Math.max(0, sectionMetric(section) - previousMetric);
    if (added > 0) {
      if (section === 'tech_stack') return 'New Tech Stack saved';
      if (section === 'projects') return added === 1 ? 'New Project saved' : `${added} new Projects saved`;
      if (section === 'milestones') return added === 1 ? 'New Milestone saved' : `${added} new Milestones saved`;
      if (section === 'industry_experiences') return 'New Industry Experience saved';
    }
    return `${sectionLabels[section]} updated`;
  }

  async function saveSection(section, button) {
    const operations = [...(pendingOperations[section] || [])];
    const operationText = operations.length ? operations.join(', ') : 'edit';
    if (!window.confirm(`Confirm ${sectionLabels[section]} save?\n\nPending operations: ${operationText}`)) {
      return;
    }

    prepareSection(section);
    const previousMetric = savedMetrics[section];
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Saving...';

    try {
      const response = await fetch(window.location.pathname, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ ...state, section, operations }),
      });
      const result = await response.json();
      if (result.log) {
        logs.push(result.log);
        renderLogs();
      }
      if (result.logStored === false) {
        showToast('The attempt was written to the PHP fallback log.', true);
      }
      if (!response.ok || !result.ok) {
        throw new Error(arrayValue(result.errors).join(' ') || 'Save failed.');
      }

      const message = successMessage(section, previousMetric);
      savedMetrics[section] = sectionMetric(section);
      const savedTime = result.savedAt ? new Date(result.savedAt) : new Date();
      const time = savedTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      lastSavedMessage = `Saved at ${time}`;
      markClean(section, message);
      showToast(message);
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Save failed.';
      showError(section, message);
      showToast(message, true);
      button.disabled = false;
    } finally {
      button.textContent = originalText;
    }
  }

  function confirmRemoval(label) {
    return window.confirm(`Delete ${label}? This change is permanent after you save the section.`);
  }

  function confirmAddition(label) {
    return window.confirm(`Add ${label}? You will still need to save the section.`);
  }

  function swapItems(items, firstIndex, secondIndex) {
    [items[firstIndex], items[secondIndex]] = [items[secondIndex], items[firstIndex]];
  }

  function moveItem(items, index, direction, section, render) {
    const target = index + direction;
    if (target < 0 || target >= items.length) return;
    swapItems(items, index, target);
    markDirty(section, 'reorder');
    render();
  }

  function moveGroupedItem(items, index, groupIndexes, direction, section) {
    const position = groupIndexes.indexOf(index);
    const targetPosition = position + direction;
    if (targetPosition < 0 || targetPosition >= groupIndexes.length) return;
    swapItems(items, index, groupIndexes[targetPosition]);
    markDirty(section, 'reorder');
    renderPortfolioSection(section);
  }

  function recordShell(index, title, controls, toneSeed = '') {
    const card = createElement('article', { className: 'record-card', dataset: { tone: toneFor(toneSeed || index) } });
    const heading = createElement('div', { className: 'record-heading' }, [
      createElement('span', { className: 'record-index', text: String(index + 1).padStart(2, '0') }),
      createElement('p', { className: 'record-title', text: title || 'Untitled record' }),
    ]);
    const toolbar = createElement('header', { className: 'record-toolbar' }, [
      heading,
      createElement('div', { className: 'record-controls' }, controls),
    ]);
    const body = createElement('div', { className: 'record-body' });
    card.append(toolbar, body);
    return { card, body, titlePreview: heading.lastElementChild };
  }

  function createReorderControls(canMoveUp, canMoveDown, onUp, onDown, onDelete) {
    const up = makeButton('', 'icon-button', 'Move up');
    const down = makeButton('', 'icon-button', 'Move down');
    const remove = makeButton('Delete', 'button button-danger delete-button', 'Delete record');
    up.setAttribute('aria-label', 'Move up');
    down.setAttribute('aria-label', 'Move down');
    up.append(makeIcon('up'));
    down.append(makeIcon('down'));
    remove.prepend(makeIcon('delete'));
    up.disabled = !canMoveUp;
    down.disabled = !canMoveDown;
    up.addEventListener('click', onUp);
    down.addEventListener('click', onDown);
    remove.addEventListener('click', onDelete);
    return [up, down, remove];
  }

  function createTokenEditor(item, section) {
    const panel = createElement('div', { className: 'record-subpanel' });
    const list = createElement('div', { className: 'token-list' });
    const add = makeButton('+ Add tech', 'button button-quiet');
    const heading = createElement('div', { className: 'subpanel-heading' }, [
      createElement('strong', { text: 'Technologies' }),
      add,
    ]);

    function render() {
      list.replaceChildren();
      const values = arrayValue(item.techStack);
      if (!values.length) {
        list.append(emptyState('No technologies assigned.'));
        return;
      }
      values.forEach((tag, index) => {
        const input = makeInput(tag, 'Technology', (value) => {
          values[index] = value;
          markDirty(section);
        });
        input.addEventListener('change', () => {
          if (input.value.trim() !== '' || String(tag).trim() === '') return;
          if (!confirmRemoval(`technology "${tag}"`)) {
            values[index] = tag;
            input.value = tag;
            return;
          }
          values.splice(index, 1);
          markDirty(section, 'delete');
          render();
        });
        const remove = makeButton('x', 'icon-button button-danger', 'Remove technology');
        remove.addEventListener('click', () => {
          if (!confirmRemoval(`technology "${tag || index + 1}"`)) return;
          values.splice(index, 1);
          markDirty(section, 'delete');
          render();
        });
        list.append(createElement('div', { className: 'token-row' }, [input, remove]));
      });
    }

    add.addEventListener('click', () => {
      if (!confirmAddition('a technology to this record')) return;
      if (!Array.isArray(item.techStack)) item.techStack = [];
      item.techStack.unshift('');
      markDirty(section, 'add');
      render();
      list.querySelector('input')?.focus();
    });
    panel.append(heading, list);
    render();
    return panel;
  }

  function createActionsEditor(item, section) {
    const panel = createElement('div', { className: 'record-subpanel' });
    const list = createElement('div', { className: 'action-list' });
    const add = makeButton('+ Add action', 'button button-quiet');
    const heading = createElement('div', { className: 'subpanel-heading' }, [
      createElement('strong', { text: 'Actions and destinations' }),
      add,
    ]);

    function render() {
      list.replaceChildren();
      const actions = arrayValue(item.actions);
      if (!actions.length) {
        list.append(emptyState('No actions configured.'));
        return;
      }
      actions.forEach((actionValue, index) => {
        const action = objectValue(actionValue);
        actions[index] = action;
        const label = makeInput(action.label ?? '', 'view', (value) => {
          action.label = value;
          markDirty(section);
        });
        const type = makeSelect(action.type ?? 'external', [
          { value: 'external', text: 'External' },
          { value: 'modal', text: 'README modal' },
        ], (value) => {
          action.type = value;
          markDirty(section);
        });
        const url = makeInput(action.url ?? '', 'https://...', (value) => {
          action.url = value;
          markDirty(section);
        });
        const source = makeInput(action.source ?? '', 'content/readmes/...md', (value) => {
          action.source = value;
          markDirty(section);
        });
        const remove = makeButton('x', 'icon-button button-danger', 'Remove action');
        remove.addEventListener('click', () => {
          if (!confirmRemoval(`action "${action.label || index + 1}"`)) return;
          actions.splice(index, 1);
          markDirty(section, 'delete');
          render();
        });
        list.append(createElement('div', { className: 'action-row' }, [
          makeField('Label', label),
          makeField('Type', type),
          makeField('External URL', url, 'action-url'),
          makeField('README source', source, 'action-source'),
          remove,
        ]));
      });
    }

    add.addEventListener('click', () => {
      if (!confirmAddition('a new action')) return;
      if (!Array.isArray(item.actions)) item.actions = [];
      item.actions.unshift({ label: '', type: 'external', url: '' });
      markDirty(section, 'add');
      render();
      list.querySelector('input')?.focus();
    });
    panel.append(heading, list);
    render();
    return panel;
  }

  function createPortfolioCard(section, item, index, groupIndexes) {
    const items = state[section];
    const groupPosition = groupIndexes.indexOf(index);
    const singular = section === 'projects' ? 'project' : 'milestone';
    const controls = createReorderControls(
      groupPosition > 0,
      groupPosition < groupIndexes.length - 1,
      () => moveGroupedItem(items, index, groupIndexes, -1, section),
      () => moveGroupedItem(items, index, groupIndexes, 1, section),
      () => {
        if (!confirmRemoval(`${singular} "${item.title || `#${index + 1}`}"`)) return;
        items.splice(index, 1);
        markDirty(section, 'delete');
        renderPortfolioSection(section);
      }
    );
    const shell = recordShell(index, item.title, controls, item.group);
    const title = makeInput(item.title ?? '', `${singular} title`, (value) => {
      item.title = value;
      shell.titlePreview.textContent = value || 'Untitled record';
      markDirty(section);
    });
    const group = makeInput(item.group ?? '', 'Group', (value) => {
      item.group = value;
      markDirty(section);
    });
    group.addEventListener('change', () => {
      activePortfolioGroup[section] = group.value.trim() || 'Ungrouped';
      renderPortfolioSection(section);
    });
    const description = makeTextarea(item.description ?? '', 'record-description', (value) => {
      item.description = value;
      if (counter) counter.textContent = `${value.length}/150`;
      markDirty(section);
    });
    let counter = null;
    if (section === 'projects') {
      description.maxLength = 150;
      counter = createElement('span', { className: 'character-count', text: `${description.value.length}/150` });
    }

    shell.body.append(
      createElement('div', { className: 'record-fields' }, [
        makeField('Title', title),
        makeField('Group', group),
      ]),
      makeField('Description', description, '', counter),
      createTokenEditor(item, section),
      createActionsEditor(item, section)
    );
    return shell.card;
  }

  function renderPortfolioSection(section) {
    const container = document.getElementById(section === 'projects' ? 'projectsList' : 'milestonesList');
    const items = arrayValue(state[section]);
    container.replaceChildren();
    updateCounts();
    if (!items.length) {
      container.append(emptyState(`No ${section} yet.`));
      return;
    }

    const groups = new Map();
    items.forEach((itemValue, index) => {
      const item = objectValue(itemValue);
      items[index] = item;
      const group = String(item.group ?? '').trim() || 'Ungrouped';
      if (!groups.has(group)) groups.set(group, []);
      groups.get(group).push(index);
    });

    const groupNames = [...groups.keys()];
    if (!groups.has(activePortfolioGroup[section])) activePortfolioGroup[section] = groupNames[0];

    const tabs = createElement('div', {
      className: 'group-tabs',
    });
    tabs.setAttribute('role', 'tablist');
    tabs.setAttribute('aria-label', section === 'projects' ? 'Project groups' : 'Milestone groups');
    groupNames.forEach((group) => {
      const isActive = group === activePortfolioGroup[section];
      tabs.append(makeGroupTab(group, groups.get(group).length, toneFor(group), isActive, () => {
        activePortfolioGroup[section] = group;
        renderPortfolioSection(section);
      }));
    });

    const activeGroup = activePortfolioGroup[section];
    const activeIndexes = groups.get(activeGroup);
    const grid = createElement('div', { className: 'record-grid' });
    activeIndexes.forEach((index) => grid.append(createPortfolioCard(section, items[index], index, activeIndexes)));
    const heading = createElement('header', { className: 'group-heading' }, [
      createElement('div', {}, [
        createElement('h3', { text: activeGroup }),
        createElement('p', { text: `${activeIndexes.length} ${activeIndexes.length === 1 ? 'record' : 'records'}` }),
      ]),
      createElement('code', { text: section === 'projects' ? 'PROJECT GROUP' : 'MILESTONE GROUP' }),
    ]);
    container.append(tabs, createElement('section', { className: 'group-block' }, [heading, grid]));
  }

  function renderTechStack() {
    const container = document.getElementById('techStackList');
    container.replaceChildren();
    updateCounts();
    if (!state.tech_stack.length) {
      container.append(emptyState('No technologies yet.'));
      return;
    }
    state.tech_stack.forEach((tag, index) => {
      const input = makeInput(tag, 'Technology', (value) => {
        state.tech_stack[index] = value;
        markDirty('tech_stack');
      });
      input.addEventListener('change', () => {
        if (input.value.trim() === '' && String(tag).trim() !== '') {
          if (!confirmRemoval(`technology "${tag}"`)) {
            state.tech_stack[index] = tag;
            input.value = tag;
            return;
          }
          state.tech_stack.splice(index, 1);
          markDirty('tech_stack', 'delete');
          renderTechStack();
          return;
        }
        state.tech_stack = cleanTags(state.tech_stack);
        renderTechStack();
      });
      const remove = makeButton('x', 'icon-button button-danger', 'Remove technology');
      remove.addEventListener('click', () => {
        if (!confirmRemoval(`technology "${tag || index + 1}"`)) return;
        state.tech_stack.splice(index, 1);
        markDirty('tech_stack', 'delete');
        renderTechStack();
      });
      container.append(createElement('div', { className: 'token-row' }, [input, remove]));
    });
  }

  function renderAchievements() {
    const container = document.getElementById('achievementsList');
    const items = state.industry_experiences.keyAchievements;
    container.replaceChildren();
    updateCounts();
    if (!items.length) {
      container.append(emptyState('No achievements yet.'));
      return;
    }

    items.forEach((itemValue, index) => {
      const item = objectValue(itemValue);
      items[index] = item;
      const controls = createReorderControls(
        index > 0,
        index < items.length - 1,
        () => moveItem(items, index, -1, 'industry_experiences', renderAchievements),
        () => moveItem(items, index, 1, 'industry_experiences', renderAchievements),
        () => {
          if (!confirmRemoval(`achievement "${item.title || `#${index + 1}`}"`)) return;
          items.splice(index, 1);
          markDirty('industry_experiences', 'delete');
          renderAchievements();
        }
      );
      const shell = recordShell(index, item.title, controls, `achievement-${index}`);
      const title = makeInput(item.title ?? '', 'Achievement title', (value) => {
        item.title = value;
        shell.titlePreview.textContent = value || 'Untitled achievement';
        markDirty('industry_experiences');
      });
      const summary = makeTextarea(item.summary ?? '', 'achievement-summary', (value) => {
        item.summary = value;
        markDirty('industry_experiences');
      });
      shell.body.append(makeField('Title', title), makeField('Summary', summary));
      container.append(shell.card);
    });
  }

  function legacyLogValue(before, after, path = '') {
    if (JSON.stringify(before) === JSON.stringify(after)) return [];
    const beforeObject = before && typeof before === 'object';
    const afterObject = after && typeof after === 'object';
    if (!beforeObject || !afterObject) {
      return [`${path || 'value'}: ${JSON.stringify(before)} -> ${JSON.stringify(after)}`];
    }

    const keys = new Set([...Object.keys(before), ...Object.keys(after)]);
    return [...keys].flatMap((key) => legacyLogValue(
      before[key],
      after[key],
      path ? `${path}.${key}` : key
    ));
  }

  function renderLogs() {
    const tableBody = document.getElementById('logsTableBody');
    if (!tableBody) return;
    tableBody.replaceChildren();
    setText('logsCount', logs.length);

    if (!logs.length) {
      const cell = createElement('td', { colSpan: 4 });
      cell.append(emptyState('No save attempts have been logged yet.'));
      tableBody.append(createElement('tr', {}, [cell]));
      return;
    }

    [...logs].reverse().forEach((logValue) => {
      const log = objectValue(logValue);
      const timestamp = new Date(log.timestamp || '');
      const time = Number.isNaN(timestamp.getTime())
        ? String(log.timestamp || '-')
        : timestamp.toLocaleString([], { dateStyle: 'medium', timeStyle: 'medium' });
      const status = String(log.status || 'unknown');
      const section = sectionLabels[log.section] || String(log.section || 'Unknown').replaceAll('_', ' ');
      const legacyValue = legacyLogValue(log.before ?? null, log.after ?? null).join('\n');
      const value = String(log.value || legacyValue || arrayValue(log.errors).join(' ') || 'No value recorded.');
      tableBody.append(createElement('tr', {}, [
        createElement('td', { text: time }),
        createElement('td', {}, [createElement('span', { className: `log-status is-${status}`, text: status })]),
        createElement('td', { text: section }),
        createElement('td', {}, [createElement('pre', { className: 'log-value', text: value })]),
      ]));
    });
  }

  function setExperienceTab(tab) {
    activeExperienceTab = tab;
    document.querySelectorAll('[data-experience-tab]').forEach((button) => {
      const isActive = button.dataset.experienceTab === tab;
      button.classList.toggle('is-active', isActive);
      button.setAttribute('aria-selected', String(isActive));
      button.tabIndex = isActive ? 0 : -1;
    });
    document.querySelectorAll('[data-experience-panel]').forEach((panel) => {
      panel.hidden = panel.dataset.experiencePanel !== tab;
    });
  }

  function renderRoles() {
    const container = document.getElementById('rolesList');
    const items = state.industry_experiences.roles;
    container.replaceChildren();
    updateCounts();
    if (!items.length) {
      container.append(emptyState('No career roles yet.'));
      return;
    }

    items.forEach((itemValue, index) => {
      const item = objectValue(itemValue);
      items[index] = item;
      const controls = createReorderControls(
        index > 0,
        index < items.length - 1,
        () => moveItem(items, index, -1, 'industry_experiences', renderRoles),
        () => moveItem(items, index, 1, 'industry_experiences', renderRoles),
        () => {
          if (!confirmRemoval(`role "${item.role || `#${index + 1}`}"`)) return;
          items.splice(index, 1);
          markDirty('industry_experiences', 'delete');
          renderRoles();
        }
      );
      const shell = recordShell(index, item.role, controls, `role-${index}`);
      const role = makeInput(item.role ?? '', 'Role title', (value) => {
        item.role = value;
        shell.titlePreview.textContent = value || 'Untitled role';
        markDirty('industry_experiences');
      });
      const from = makeInput(item.from ?? '', 'From', (value) => {
        item.from = value;
        markDirty('industry_experiences');
      });
      const to = makeInput(item.to ?? '', 'To', (value) => {
        item.to = value;
        markDirty('industry_experiences');
      });
      const current = createElement('input', { type: 'checkbox', checked: item.current === true });
      current.addEventListener('change', () => {
        item.current = current.checked;
        markDirty('industry_experiences');
      });
      const scope = makeTextarea(item.scope ?? '', 'role-scope', (value) => {
        item.scope = value;
        markDirty('industry_experiences');
      });
      shell.body.append(
        createElement('div', { className: 'field-grid field-grid-3' }, [
          makeField('Role', role),
          makeField('From', from),
          makeField('To', to),
        ]),
        createElement('label', { className: 'current-control' }, [
          current,
          createElement('span', { text: 'Mark as current role' }),
        ]),
        makeField('Scope and responsibilities', scope)
      );
      container.append(shell.card);
    });
  }

  function existingGroups(section) {
    const groups = [];
    state[section].forEach((item) => {
      const group = String(item?.group ?? '').trim();
      if (group && !groups.includes(group)) groups.push(group);
    });
    return groups;
  }

  function toggleNewGroupField() {
    const select = document.getElementById('entryGroupSelect');
    const field = document.getElementById('newGroupField');
    field.hidden = select.value !== '__new__';
    if (!field.hidden) document.getElementById('entryNewGroup').focus();
  }

  function openAddDialog(section) {
    activeDialogSection = section;
    const dialog = document.getElementById('addEntryDialog');
    const select = document.getElementById('entryGroupSelect');
    const groups = existingGroups(section);
    const singular = section === 'projects' ? 'project' : 'milestone';
    document.getElementById('addEntryTitle').textContent = `Add ${singular}`;
    document.getElementById('confirmEntryAdd').textContent = `Add ${singular}`;
    document.getElementById('entryNewGroup').value = '';
    document.getElementById('entryDialogError').textContent = '';
    select.replaceChildren();
    groups.forEach((group) => select.append(createElement('option', { value: group, text: group })));
    select.append(createElement('option', { value: '__new__', text: '+ Create a new group' }));
    if (!groups.length) select.value = '__new__';
    toggleNewGroupField();
    dialog.showModal();
  }

  function closeAddDialog() {
    document.getElementById('addEntryDialog').close();
    activeDialogSection = '';
  }

  document.querySelectorAll('[data-path]').forEach((control) => {
    const eventName = control.tagName === 'SELECT' ? 'change' : 'input';
    control.addEventListener(eventName, () => {
      setAtPath(control.dataset.path, control.type === 'checkbox' ? control.checked : control.value);
      markDirty(control.dataset.section);
    });
  });

  document.querySelectorAll('[data-save-section]').forEach((button) => {
    button.addEventListener('click', () => saveSection(button.dataset.saveSection, button));
  });

  document.getElementById('addTechButton').addEventListener('click', () => {
    if (!confirmAddition('a new technology')) return;
    state.tech_stack.unshift('');
    markDirty('tech_stack', 'add');
    renderTechStack();
    document.querySelector('#techStackList input')?.focus();
  });

  document.querySelectorAll('[data-open-add-dialog]').forEach((button) => {
    button.addEventListener('click', () => openAddDialog(button.dataset.openAddDialog));
  });

  document.getElementById('entryGroupSelect').addEventListener('change', toggleNewGroupField);
  document.getElementById('closeEntryDialog').addEventListener('click', closeAddDialog);
  document.getElementById('cancelEntryDialog').addEventListener('click', closeAddDialog);
  document.getElementById('addEntryDialog').addEventListener('click', (event) => {
    if (event.target === event.currentTarget) closeAddDialog();
  });
  document.getElementById('addEntryForm').addEventListener('submit', (event) => {
    event.preventDefault();
    if (!activeDialogSection) return;
    const select = document.getElementById('entryGroupSelect');
    const newGroup = document.getElementById('entryNewGroup');
    const group = (select.value === '__new__' ? newGroup.value : select.value).trim();
    if (!group) {
      document.getElementById('entryDialogError').textContent = 'Enter a group name before adding the record.';
      newGroup.focus();
      return;
    }
    const singular = activeDialogSection === 'projects' ? 'project' : 'milestone';
    if (!confirmAddition(`this ${singular} in "${group}"`)) return;
    state[activeDialogSection].unshift({
      title: '',
      group,
      description: '',
      techStack: [],
      actions: [],
    });
    const section = activeDialogSection;
    activePortfolioGroup[section] = group;
    markDirty(section, 'add');
    renderPortfolioSection(section);
    closeAddDialog();
    window.requestAnimationFrame(() => {
      document.querySelector(`#${section} .record-card`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      document.querySelector(`#${section} .record-card input`)?.focus({ preventScroll: true });
    });
  });

  document.getElementById('addAchievementButton').addEventListener('click', () => {
    if (!confirmAddition('a new achievement')) return;
    setExperienceTab('achievements');
    state.industry_experiences.keyAchievements.unshift({ title: '', summary: '' });
    markDirty('industry_experiences', 'add');
    renderAchievements();
    document.querySelector('#achievementsList input')?.focus();
  });

  document.getElementById('addRoleButton').addEventListener('click', () => {
    if (!confirmAddition('a new career role')) return;
    setExperienceTab('roles');
    state.industry_experiences.roles.unshift({ from: '', to: '', role: '', scope: '', current: false });
    markDirty('industry_experiences', 'add');
    renderRoles();
    document.querySelector('#rolesList input')?.focus();
  });

  window.addEventListener('beforeunload', (event) => {
    if (!dirtySections.size) return;
    event.preventDefault();
    event.returnValue = '';
  });

  document.querySelectorAll('[data-experience-tab]').forEach((button) => {
    button.addEventListener('click', () => setExperienceTab(button.dataset.experienceTab));
  });

  renderTechStack();
  renderPortfolioSection('projects');
  renderPortfolioSection('milestones');
  renderAchievements();
  renderRoles();
  setExperienceTab(activeExperienceTab);
  renderLogs();
  updateOverview();
})();
