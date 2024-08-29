<template>
  <div class="login-info">
    <span>
      <!-- User image size is adjusted inside CSS, it should stay as it -->

      <a
        href="javascript:void(0);"
        id="show-shortcut"
        data-action="toggleShortcut"
      >
        <img src="/admin/img/avatars/sunny.png" alt="me" class="online" />
        <span>
          {{ adminName }} -
          <small>{{ adminEmail }}</small>
        </span>
        <i class="fa fa-angle-down"></i>
      </a>
    </span>
  </div>
</template>

<script>
import { EventBus } from "./eventbus"

export default {
  name: "UserData",
  data() {
    return {
      adminName: "",
      adminId: "",
      adminEmail: "",
    }
  },
  created() {
    EventBus.$on("adminGot", this.getAdmin)
  },
  methods: {
    getAdmin() {
      this.adminName = this.$store.state.admin.name
      this.adminId = this.$store.state.admin.id
      this.adminEmail = this.$store.state.admin.email
    },
  },
}
</script>

<style scoped></style>
