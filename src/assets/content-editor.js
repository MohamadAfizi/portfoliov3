(() => {
  'use strict';

  const clone = (value) => JSON.parse(JSON.stringify(value));
  const state = clone(window.CONTENT_EDITOR_DATA || {});
  const dirtySections = new Set();
  const sectionLabels = {
    site: 'Site & Profile',
    tech_stack: 'Tech Stack',
    projects: 'Projects',
    milestones: 'Milestones',
    industry_experiences: 'Industry Experience',
  };
  let activeDialogSection = '';
  let controlId = 0;
  let lastSavedMessage = '';

  state.site = objectValue(state.site);
  state.navigation = objectValue(state.navigation);
  state.ui = objectValue(state.ui);
  state.tech_stack = arrayValue(state.tech_stack);
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
      const roles = arrayValue(industry.roles);
      return arrayValue(industry.keyAchievements).length
        + roles.length
        + roles.reduce((total, role) => total + arrayValue(role?.positions).length, 0);
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

  function markDirty(section) {
    dirtySections.add(section);
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
      if (value && !cleaned.includes(value)) cleaned.push(value);
    });
    return cleaned;
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
    prepareSection(section);
    const previousMetric = savedMetrics[section];
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Saving...';

    try {
      const response = await fetch(window.location.pathname, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ ...state, section }),
      });
      const result = await response.json();
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

  function swapItems(items, firstIndex, secondIndex) {
    [items[firstIndex], items[secondIndex]] = [items[secondIndex], items[firstIndex]];
  }

  function moveItem(items, index, direction, section, render) {
    const target = index + direction;
    if (target < 0 || target >= items.length) return;
    swapItems(items, index, target);
    markDirty(section);
    render();
  }

  function moveGroupedItem(items, index, groupIndexes, direction, section) {
    const position = groupIndexes.indexOf(index);
    const targetPosition = position + direction;
    if (targetPosition < 0 || targetPosition >= groupIndexes.length) return;
    swapItems(items, index, groupIndexes[targetPosition]);
    markDirty(section);
    renderPortfolioSection(section);
  }

  function recordShell(index, title, controls) {
    const card = createElement('article', { className: 'record-card' });
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
    const up = makeButton('up', 'icon-button', 'Move up');
    const down = makeButton('dn', 'icon-button', 'Move down');
    const remove = makeButton('x', 'icon-button button-danger', 'Delete record');
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
        const remove = makeButton('x', 'icon-button button-danger', 'Remove technology');
        remove.addEventListener('click', () => {
          values.splice(index, 1);
          markDirty(section);
          render();
        });
        list.append(createElement('div', { className: 'token-row' }, [input, remove]));
      });
    }

    add.addEventListener('click', () => {
      if (!Array.isArray(item.techStack)) item.techStack = [];
      item.techStack.unshift('');
      markDirty(section);
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
          markDirty(section);
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
      if (!Array.isArray(item.actions)) item.actions = [];
      item.actions.unshift({ label: '', type: 'external', url: '' });
      markDirty(section);
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
        markDirty(section);
        renderPortfolioSection(section);
      }
    );
    const shell = recordShell(index, item.title, controls);
    const title = makeInput(item.title ?? '', `${singular} title`, (value) => {
      item.title = value;
      shell.titlePreview.textContent = value || 'Untitled record';
      markDirty(section);
    });
    const group = makeInput(item.group ?? '', 'Group', (value) => {
      item.group = value;
      markDirty(section);
    });
    group.addEventListener('change', () => renderPortfolioSection(section));
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

    groups.forEach((indexes, group) => {
      const grid = createElement('div', { className: 'record-grid' });
      indexes.forEach((index) => grid.append(createPortfolioCard(section, items[index], index, indexes)));
      const heading = createElement('header', { className: 'group-heading' }, [
        createElement('div', {}, [
          createElement('h3', { text: group }),
          createElement('p', { text: `${indexes.length} ${indexes.length === 1 ? 'record' : 'records'}` }),
        ]),
        createElement('code', { text: section === 'projects' ? 'PROJECT GROUP' : 'MILESTONE GROUP' }),
      ]);
      container.append(createElement('section', { className: 'group-block' }, [heading, grid]));
    });
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
      const remove = makeButton('x', 'icon-button button-danger', 'Remove technology');
      remove.addEventListener('click', () => {
        state.tech_stack.splice(index, 1);
        markDirty('tech_stack');
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
          markDirty('industry_experiences');
          renderAchievements();
        }
      );
      const shell = recordShell(index, item.title, controls);
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

  function createPositionsEditor(role) {
    const panel = createElement('div', { className: 'record-subpanel' });
    const list = createElement('div', { className: 'position-list' });
    const add = makeButton('+ Add position', 'button button-quiet');
    const heading = createElement('div', { className: 'subpanel-heading' }, [
      createElement('strong', { text: 'Nested positions' }),
      add,
    ]);

    function render() {
      list.replaceChildren();
      const positions = arrayValue(role.positions);
      if (!positions.length) {
        list.append(emptyState('No nested positions for this role.'));
        return;
      }
      positions.forEach((positionValue, index) => {
        const position = objectValue(positionValue);
        positions[index] = position;
        const roleName = makeInput(position.role ?? '', 'Position', (value) => {
          position.role = value;
          markDirty('industry_experiences');
        });
        const from = makeInput(position.from ?? '', 'From', (value) => {
          position.from = value;
          markDirty('industry_experiences');
        });
        const to = makeInput(position.to ?? '', 'To', (value) => {
          position.to = value;
          markDirty('industry_experiences');
        });
        const remove = makeButton('x', 'icon-button button-danger', 'Remove nested position');
        remove.addEventListener('click', () => {
          if (!confirmRemoval(`position "${position.role || index + 1}"`)) return;
          positions.splice(index, 1);
          markDirty('industry_experiences');
          render();
          updateCounts();
        });
        list.append(createElement('div', { className: 'position-row' }, [
          makeField('Position', roleName),
          makeField('From', from),
          makeField('To', to),
          remove,
        ]));
      });
    }

    add.addEventListener('click', () => {
      if (!Array.isArray(role.positions)) role.positions = [];
      role.positions.unshift({ role: '', from: '', to: '' });
      markDirty('industry_experiences');
      render();
      updateCounts();
      list.querySelector('input')?.focus();
    });
    panel.append(heading, list);
    render();
    return panel;
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
          markDirty('industry_experiences');
          renderRoles();
        }
      );
      const shell = recordShell(index, item.role, controls);
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
        makeField('Scope and responsibilities', scope),
        createPositionsEditor(item)
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
    state.tech_stack.unshift('');
    markDirty('tech_stack');
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
    state[activeDialogSection].unshift({
      title: '',
      group,
      description: '',
      techStack: [],
      actions: [],
    });
    const section = activeDialogSection;
    markDirty(section);
    renderPortfolioSection(section);
    closeAddDialog();
    window.requestAnimationFrame(() => {
      document.querySelector(`#${section} .record-card`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      document.querySelector(`#${section} .record-card input`)?.focus({ preventScroll: true });
    });
  });

  document.getElementById('addAchievementButton').addEventListener('click', () => {
    state.industry_experiences.keyAchievements.unshift({ title: '', summary: '' });
    markDirty('industry_experiences');
    renderAchievements();
    document.querySelector('#achievementsList input')?.focus();
  });

  document.getElementById('addRoleButton').addEventListener('click', () => {
    state.industry_experiences.roles.unshift({ from: '', to: '', role: '', scope: '', current: false });
    markDirty('industry_experiences');
    renderRoles();
    document.querySelector('#rolesList input')?.focus();
  });

  window.addEventListener('beforeunload', (event) => {
    if (!dirtySections.size) return;
    event.preventDefault();
    event.returnValue = '';
  });

  renderTechStack();
  renderPortfolioSection('projects');
  renderPortfolioSection('milestones');
  renderAchievements();
  renderRoles();
  updateOverview();
})();
