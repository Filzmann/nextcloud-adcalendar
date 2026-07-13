(function() {
    'use strict';

    /**
     * Zweck: Kapselt URL-, Filter-, Tab- und persönlichen Standardzustand der Kalenderansicht.
     * Zusammenspiel: main.js mutiert fachliche UI-Aktionen und delegiert Persistenz sowie Personenfilter an diese Klasse.
     */
    class CalendarState {
        constructor(leadershipStaffRoles, location = window.location, history = window.history) {
            this.leadershipStaffRoles = leadershipStaffRoles;
            this.location = location;
            this.history = history;
            this.monday = this.startOfWeek(new Date());
            this.data = null;
            this.vertical = true;
            this.selected = new Set();
            this.roles = new Set();
            this.areas = new Set();
            this.showLeadershipStaff = true;
            this.activeTab = 'calendar';
            this.filtersInitialized = false;
            this.urlConfigured = false;
            this.emptyOwnProfile = false;
        }

        restore() {
            const params = new URLSearchParams(this.location.search);
            if (params.get('week')) this.monday = this.startOfWeek(new Date(`${params.get('week')}T12:00:00`));
            this.urlConfigured = ['people', 'roles', 'areas', 'view', 'staff'].some(key => params.has(key));
            this.vertical = params.get('view') !== 'days';
            this.showLeadershipStaff = params.get('staff') !== 'hidden';
            this.activeTab = params.get('tab') === 'settings' ? 'settings' : 'calendar';
            this.selected = this.values(params, 'people');
            this.roles = this.values(params, 'roles');
            this.areas = this.values(params, 'areas');
            return this;
        }

        persist() {
            const params = new URLSearchParams();
            params.set('week', this.isoDay(this.monday));
            if (!this.vertical) params.set('view', 'days');
            params.set('staff', this.showLeadershipStaff ? 'visible' : 'hidden');
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
                this.showLeadershipStaff = usesOwnProfile
                    ? filterRoles.some(role => this.leadershipStaffRoles.has(role))
                    : filters.showLeadershipStaff !== false;
                this.emptyOwnProfile = filters.empty === true || (usesOwnProfile && this.roles.size === 0 && this.areas.size === 0);
            }
            this.filtersInitialized = true;
        }

        availableEmployees() {
            if (!this.data) return [];
            return this.data.employees.filter(employee => {
                const isLeadershipStaff = employee.roles.some(role => this.leadershipStaffRoles.has(role));
                if (this.selected.size) return this.selected.has(employee.uid) && (!isLeadershipStaff || this.showLeadershipStaff);
                if (isLeadershipStaff) return this.showLeadershipStaff;
                if (this.emptyOwnProfile) return false;
                if (this.roles.size && !employee.roles.some(role => this.roles.has(role))) return false;
                if (this.areas.size && !employee.areas.some(area => this.areas.has(area))) return false;
                return true;
            });
        }

        toPreference() {
            return {
                people: [...this.selected], roles: [...this.roles], areas: [...this.areas],
                vertical: this.vertical, empty: this.emptyOwnProfile,
                showLeadershipStaff: this.showLeadershipStaff,
            };
        }

        startOfWeek(value) { const result = new Date(value); const weekday = result.getDay() || 7; result.setDate(result.getDate() - weekday + 1); result.setHours(0, 0, 0, 0); return result; }
        isoDay(value) { const year = value.getFullYear(); const month = String(value.getMonth() + 1).padStart(2, '0'); const day = String(value.getDate()).padStart(2, '0'); return `${year}-${month}-${day}`; }
        values(params, key) { return new Set((params.get(key) || '').split(',').filter(Boolean)); }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.modules = window.AdCalendar.modules || {};
    window.AdCalendar.modules.CalendarState = CalendarState;
})();
