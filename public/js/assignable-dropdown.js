document.addEventListener('alpine:init', () => {

    Alpine.data('assignableMulti', () => ({
        users: [],
        search: '',
        loading: false,
        selectedUsers: [],

        init() {
            this.fetchUsers();

            this.$watch('search', value => {
                this.fetchUsers();
            });
        },

        async fetchUsers() {
            this.loading = true;

            try {
                let response = await fetch(
                    `/assignables?type=faculty&search=${this.search}`
                );

                this.users = await response.json();

            } catch (error) {
                console.error(error);
                this.users = [];
            }

            this.loading = false;
        },

        toggleSelection(id) {
            if (this.selectedUsers.includes(id)) {
                this.selectedUsers = this.selectedUsers.filter(userId => userId !== id);
            } else {
                this.selectedUsers.push(id);
            }
        },

        isSelected(id) {
            return this.selectedUsers.includes(id);
        }

    }));

});
