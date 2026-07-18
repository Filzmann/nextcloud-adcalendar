(function() {
    'use strict';
    const CalendarDate = window.AdCalendar.modules.CalendarDate;

    /**
     * Zweck: Kapselt URL-, Filter-, Tab- und persönlichen Standardzustand der Kalenderansicht.
     * Zusammenspiel: main.js mutiert fachliche UI-Aktionen und delegiert Persistenz sowie Personenfilter an diese Klasse.
     */
    class CalendarState {
        constructor(leadershipStaffRoles, location = window.location, history = window.history) {
            this.leadershipStaffRoles = leadershipStaffRoles;
            this.location = location;
            this.history = history;
            this.monday = CalendarDate.startOfWeek(new Date());
            this.data = null;
            this.vertical = true;
            this.selected = new Set();
            this.roles = new Set();
            this.areas = new Set();
            this.showLeadershipStaff = true;
            this.leadershipStaffOnly = false;
            this.activeTab = 'calendar';
            this.filtersInitialized = false;
            this.urlConfigured = false;
        }

        restore() {
            const params = new URLSearchParams(this.location.search);
            if (params.get('week')) this.monday = CalendarDate.startOfWeek(new Date(`${params.get('week')}T12:00:00`));
            this.urlConfigured = ['people', 'roles', 'areas', 'view', 'staff', 'staffOnly'].some(key => params.has(key));
            this.vertical = params.get('view') !== 'days';
            this.showLeadershipStaff = params.get('staff') !== 'hidden';
            this.leadershipStaffOnly = this.showLeadershipStaff && params.get('staffOnly') === '1';
            this.activeTab = params.get('tab') === 'settings' ? 'settings' : 'calendar';
            this.selected = this.values(params, 'people');
            this.roles = this.values(params, 'roles');
            this.areas = this.values(params, 'areas');
            return this;
        }

        persist() {
            const params = new URLSearchParams();
            params.set('week', CalendarDate.isoDay(this.monday));
            if (!this.vertical) params.set('view', 'days');
            params.set('staff', this.showLeadershipStaff ? 'visible' : 'hidden');
            if (this.leadershipStaffOnly) params.set('staffOnly', '1');
            if (this.activeTab === 'settings') params.set('tab', 'settings');
            if (this.selected.size) params.set('people', [...this.selected].join(','));
            if (this.roles.size) params.set('roles', [...this.roles].join(','));
            if (this.areas.size) params.set('areas', [...this.areas].join(','));
            this.history.replaceState(null, '', `${this.location.pathname}?${params}`);
        }

        applyInitialFilters() {
            if (this.filtersInitialized) return;
            if (!this.urlConfigured) {
                const usesOwnProfile = this.data.defaultFilters === null;
                const filters = this.data.defaultFilters || {
                    people: [], roles: this.data.currentUserProfile?.roles || [],
                    areas: this.data.currentUserProfile?.areas || [], vertical: true,
                };
                const filterRoles = filters.roles || [];
                this.selected = new Set(filters.people || []);
                this.roles = new Set(filterRoles.filter(role => !this.leadershipStaffRoles.has(role)));
                this.areas = new Set(filters.areas || []);
                this.vertical = filters.vertical !== false;
                const profileContainsLeadershipStaff = filterRoles.some(role => this.leadershipStaffRoles.has(role));
                this.showLeadershipStaff = usesOwnProfile
                    ? profileContainsLeadershipStaff
                    : filters.showLeadershipStaff !== false;
                this.leadershipStaffOnly = this.showLeadershipStaff && (filters.leadershipStaffOnly === true
                    || (usesOwnProfile && profileContainsLeadershipStaff && this.roles.size === 0 && this.areas.size === 0));
            }
            this.filtersInitialized = true;
        }

        availableEmployees() {
            if (!this.data) return [];
            if (this.isUnfiltered()) return this.data.employees;
            return this.data.employees.filter(employee => {
                const isLeadershipStaff = employee.roles.some(role => this.leadershipStaffRoles.has(role));
                if (this.selected.size) return this.selected.has(employee.uid) && (!isLeadershipStaff || this.showLeadershipStaff);
                if (this.leadershipStaffOnly) return isLeadershipStaff;
                if (isLeadershipStaff) return this.showLeadershipStaff;
                if (this.roles.size && !this.roles.has(employee.roles[0] || '')) return false;
                if (this.areas.size && !employee.areas.some(area => this.areas.has(area))) return false;
                return true;
            });
        }

        toPreference() {
            return {
                people: [...this.selected], roles: [...this.roles], areas: [...this.areas],
                vertical: this.vertical,
                showLeadershipStaff: this.showLeadershipStaff,
                leadershipStaffOnly: this.leadershipStaffOnly,
            };
        }

        isUnfiltered() { return this.selected.size === 0 && this.roles.size === 0 && this.areas.size === 0 && !this.leadershipStaffOnly; }

        values(params, key) { return new Set((params.get(key) || '').split(',').filter(Boolean)); }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.modules = window.AdCalendar.modules || {};
    window.AdCalendar.modules.CalendarState = CalendarState;
})();
