<template>
  <div>
    <widget :title="'Register New User'" v-if="registerUserFlag">
      <form id="review-form" class="smart-form">
        <header>User for Using MpManager</header>

        <fieldset>
          <section>
            <label class="input">
              <i class="icon-append fa fa-user"></i>
              <input
                type="text"
                v-model="user.fullName"
                placeholder="Full Name"
              />
            </label>
          </section>

          <section>
            <label class="input">
              <i class="icon-append fa fa-envelope-o"></i>
              <input
                type="text"
                v-model="user.username"
                placeholder="Ticketing System Tag."
              />
            </label>
          </section>

          <section>
            <label class="input">
              <i class="icon-append fa fa-envelope-o"></i>
              <input
                type="password"
                v-model="user.password"
                placeholder="Password"
              />
            </label>
          </section>
        </fieldset>
        <footer>
          <small>
            By any question please get in touch with
            <i class="fa fa-skype"></i>
            ako@inensus.com
            <i class="fa fa-envelope"></i>
            ako@inensus.com
          </small>
          <button type="button" class="btn btn-primary" @click="saveUser">
            Add User
          </button>
          <button type="button" class="btn btn-danger btn-sm" @click="saveUser">
            Close
          </button>
        </footer>
      </form>
    </widget>
    <widget
      :id="'registered-user-list'"
      title="Registered Users"
      :button="true"
      :buttonText="'New User'"
      :callback="showRegister"
    ></widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"

export default {
  name: "AddNewUser",
  components: { Widget },
  data() {
    return {
      registerUserFlag: false,
      user: {},
    }
  },
  methods: {
    showRegister() {
      this.registerUserFlag = true
    },
    saveUser() {
      axios.post("http://localhost:8000/register", {
        name: "Ali KEmal Özkan",
        email: "hebele@hubele.com",
        password: "3242353434543543",
        password_confirmation: "3242353434543543",
      })
    },
  },
}
</script>

<style scoped></style>
