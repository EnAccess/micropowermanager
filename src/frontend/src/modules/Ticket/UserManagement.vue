<template>
  <div class="row">
    <div v-if="showNewUser" style="margin-top: 1rem">
      <AddExternalTicketingUser
        @created="onUserCreated"
        @cancel="showNewUser = false"
      />
    </div>

    <Widget
      :title="$tc('phrases.userList')"
      :button="true"
      button-text="Add new User"
      @widgetAction="showAddUser"
      color="green"
      :subscriber="subscriber"
    >
      <md-table v-model="userList" md-sort="name" md-sort-order="asc" md-card>
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell :md-label="$tc('words.id')" md-sort-by="id" md-numeric>
            {{ item.id }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.name')" md-sort-by="name">
            {{ item.name }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.tag')" md-sort-by="tag">
            <input
              type="checkbox"
              :checked="item.isTicketingUser"
              @change="(e) => updateTicketingUser(e.target.checked, item.id)"
            />
          </md-table-cell>
          <md-table-cell
            :md-label="$tc('phrases.createdDate')"
            md-sort-by="created_at"
          >
            {{ item.created_at }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </Widget>
  </div>
</template>

<script>
import { notify } from "@/mixins/notify"
import Widget from "@/shared/Widget.vue"
import AddExternalTicketingUser from "@/modules/Ticket/AddExternalTicketingUser.vue"
import { TicketUserService } from "@/services/TicketUserService"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "UserManagement",
  mixins: [notify],
  components: { Widget, AddExternalTicketingUser },
  data() {
    return {
      subscriber: "ticket-user-list",
      ticketUserService: new TicketUserService(),
      userList: [],
      showNewUser: false,
      loading: false,
      updateModal: false,
    }
  },
  mounted() {
    this.getUsers()
    EventBus.$on("ticket.add.user", (data) => {
      this.showNewUser = data
    })
  },
  methods: {
    async getUsers() {
      try {
        this.userList = []
        await this.ticketUserService.getUsers()
        this.$set(this, "userList", this.ticketUserService.list || [])
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.userList.length,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    showAddUser() {
      this.showNewUser = true
    },
    async onUserCreated(newUser) {
      await this.getUsers()
      this.showNewUser = false
    },
    updateTicketingUser(isActivated, userId) {
      console.log(isActivated, userId)
    },
  },
}
</script>

<style scoped></style>
