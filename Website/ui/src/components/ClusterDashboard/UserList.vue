<template>
    <div>
        <md-field>

            <md-select
                @md-selected="selectUser"
                v-model="selectedUser"
                name="user"
                id="user"
                :placeholder="$tc('phrases.assignClusterManager')"
            >

                <md-option v-for="(user) in users" :value="user.id" :key="user.id">{{user.name}}</md-option>
            </md-select>

        </md-field>
    </div>

</template>

<script>
import { UserService } from '@/services/UserService'
export default {
    name: 'UserList',
    mounted () {
        this.getUserList()
    },
    data () {
        return {
            userService: new UserService(),
            users: null,
            selectedUser: null,
        }
    },
    methods: {
        selectUser (user) {
            this.selectedUser = user
            this.$emit('userSelected', user)
        },
        async getUserList () {
            try {
                this.users = await this.userService.list(0)
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message
            })
        },
    }
}
</script>

<style scoped>
</style>
