<template>
  <div class="wrapper">
    <login-card header-color="green">
      <h4 slot="title" class="card-title">{{ $tc("words.login") }}</h4>
      <md-button
        slot="buttons"
        href="javascript:void(0)"
        class="md-just-icon md-simple md-white"
      >
        <i class="fab fa-facebook-square"></i>
      </md-button>
      <md-button
        slot="buttons"
        href="javascript:void(0)"
        class="md-just-icon md-simple md-white"
      >
        <i class="fab fa-twitter"></i>
      </md-button>
      <md-button
        slot="buttons"
        href="javascript:void(0)"
        class="md-just-icon md-simple md-white"
      >
        <i class="fab fa-google-plus-g"></i>
      </md-button>
      <p slot="description" class="description">Or Be Classical</p>
      <md-field class="md-form-group" slot="inputs">
        <md-icon>face</md-icon>
        <label>First Name...</label>
        <md-input v-model="firstname"></md-input>
      </md-field>
      <md-field class="md-form-group" slot="inputs">
        <md-icon>email</md-icon>
        <label>Email...</label>
        <md-input v-model="email" type="email"></md-input>
      </md-field>
      <md-field class="md-form-group" slot="inputs">
        <md-icon>lock_outline</md-icon>
        <label>Password...</label>
        <md-input v-model="password"></md-input>
      </md-field>
      <md-button slot="footer" class="md-simple md-success md-lg">
        Get Started
      </md-button>
    </login-card>
  </div>
</template>
<script>
import LoginCard from "@/modules/Login/LoginCard"

export default {
  name: "Login",
  components: {
    LoginCard,
  },
  bodyClass: "login-page",
  data() {
    return {
      firstname: null,
      email: null,
      password: null,
    }
  },
  props: {
    header: {
      type: String,
    },
  },
  computed: {
    headerStyle() {
      return {
        backgroundImage: `url(${this.header})`,
      }
    },
  },
}
</script>

<style lang="css"></style>
