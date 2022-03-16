<template>
    <div class="row">
        <widget v-if="showNewUser"
                :title="$tc('phrases.newTicketingUser')"
                color="red"
                >
            <form class="md-layout">
                <md-card class="md-layout-item md-size-100">
                    <md-card-content>
                        <div class="md-layout md-gutter">
                            <div class="md-layout-item md-size-100">
                                <md-field :class="{'md-invalid': errors.has($tc('words.name'))}">
                                    <label>{{ $tc('words.name') }}</label>
                                    <md-input
                                        v-model="ticketUserService.newUser.name"
                                        :name="$tc('words.name')"
                                        id="name"
                                        v-validate="'required|min:3'"></md-input>
                                    <span class="md-error">{{ errors.first($tc('words.name')) }}</span>
                                </md-field>
                                <md-field :class="{'md-invalid': errors.has($tc('phrases.ticketingSystemTag'))}">
                                    <label>{{ $tc('phrases.ticketingSystemTag') }}</label>
                                    <md-input v-model="ticketUserService.newUser.tag"
                                              :name="$tc('phrases.ticketingSystemTag')"
                                              id="tag"
                                              v-validate="'required|min:3'"></md-input>
                                    <span class="md-error">{{ errors.first($tc('phrases.ticketingSystemTag')) }}</span>
                                </md-field>
                                <md-checkbox v-model="ticketUserService.newUser.outsourcing">{{ $tc('phrases.userForOutsourcing') }}
                                </md-checkbox>
                            </div>
                        </div>
                        <md-progress-bar md-mode="indeterminate" v-if="loading"/>
                    </md-card-content>

                    <md-card-actions>
                        <md-button type="button" @click="saveUser" :disabled="loading" class="md-primary md-raised md-dense">{{ $tc('words.save') }}
                        </md-button>
                        <md-button type="button" @click="showNewUser = false" class="md-accent md-raised md-dense">{{ $tc('words.close') }}</md-button>
                    </md-card-actions>
                </md-card>


            </form>

        </widget>
        <div v-if="showNewUser" style="margin-top: 1rem;"></div>


        <widget
            :title="$tc('phrases.userList')"
            :button="true"
            button-text="Add new User"
            @widgetAction="showAddUser"
            color="green"
            :subscriber="subscriber"
        >
                <md-table v-model="ticketUserService.list" md-sort="name" md-sort-order="asc" md-card>
                    <md-table-row slot="md-table-row" slot-scope="{ item }">
                        <md-table-cell :md-label="$tc('words.id')" md-sort-by="id" md-numeric>{{ item.id }}</md-table-cell>
                        <md-table-cell :md-label="$tc('words.name')" md-sort-by="name">{{ item.name }}</md-table-cell>
                        <md-table-cell :md-label="$tc('words.tag')" md-sort-by="tag">{{ item.tag }}</md-table-cell>
                        <md-table-cell :md-label="$tc('phrases.createdDate')" md-sort-by="created_at">{{ item.created_at }}
                        </md-table-cell>
                    </md-table-row>
                </md-table>
        </widget>

    </div>
</template>

<script>
import Widget from '../../shared/widget'
import { TicketUserService } from '../../services/TicketUserService'
import { EventBus } from '../../shared/eventbus'

export default {
    name: 'UserManagement',
    components: { Widget },
    data () {
        return {
            subscriber:'ticket-user-list',
            ticketUserService: new TicketUserService(),
            showNewUser: false,
            loading: false,
        }

    },

    mounted () {

        this.getUsers()
    },
    methods: {

        async saveUser () {
            let validator = await this.$validator.validateAll()
            if (validator) {
                this.loading = true
                try {
                    let userData = await this.ticketUserService.createUser(this.ticketUserService.newUser.name, this.ticketUserService.newUser.tag, this.ticketUserService.newUser.outsourcing)

                    if (userData.error != undefined) {
                        this.alertNotify('warn',  this.$tc('phrases.ticketUserNotify',2, {tag: this.ticketUserService.newUser.tag}))
                        this.loading = false
                        return
                    }
                    await this.getUsers()
                    this.alertNotify('success', this.$tc('phrases.ticketUserNotify',1))
                    this.loading = false
                } catch (e) {
                    this.loading = false
                    this.alertNotify('error', e.message)
                }
                this.showNewUser = false
                this.resetNewUser()
            }
        },
        async getUsers () {
            try {
                this.ticketUserService.list = []
                await this.ticketUserService.getUsers()
                EventBus.$emit('widgetContentLoaded',this.subscriber,this.ticketUserService.list.length)
            } catch (e) {
                this.alertNotify('error', e.message)
            }

        },
        resetNewUser () {
            this.ticketUserService.resetNewUser()
        },
        showAddUser () {
            this.showNewUser = true
        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message,
                speed: 0
            })
        },
    },
}
</script>

<style scoped>

</style>
