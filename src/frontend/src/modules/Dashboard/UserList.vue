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
        <md-option v-for="user in users" :value="user.id" :key="user.id">
          {{ user.name }}
        </md-option>
      </md-select>
    </md-field>
  </div>
</template>

<script>
import { UserService } from "@/services/UserService"
import { notify } from "@/mixins/notify"

export default {
  name: "UserList",
  mixins: [notify],
  mounted() {
    this.getUserList()
  },
  data() {
    return {
      userService: new UserService(),
      users: null,
      selectedUser: null,
    }
  },
  methods: {
    selectUser(user) {
      this.selectedUser = user
      this.$emit("userSelected", user)
    },

    async getUserList() {
      try {
        this.users = await this.userService.list()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>

<style scoped></style>
