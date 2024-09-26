<template>
  <div class="centered-container">
    <md-content class="md-elevation-3">
      <div class="title">
        <img src="http://localhost:8000/admin/img/logo.png" alt="logo" />
        <div class="md-title">MicroPowerManager</div>
        <div class="md-body-1">
          The simplest tool to manage your
          <strong>Mini-Grid</strong>
        </div>
      </div>
      <!-- end title -->

      <!-- login form -->
      <div class="form">
        <md-field>
          <label for="email">Email</label>
          <md-input v-model="login.email" id="email" autofocus></md-input>
        </md-field>

        <md-field md-has-password>
          <label for="password">Password</label>
          <md-input
            v-model="login.password"
            id="password"
            type="password"
          ></md-input>
        </md-field>
      </div>
      <!-- end login form -->

      <div class="actions md-layout md-alignment-center-space-between">
        <a href="#">Reset Password</a>
        <md-button class="md-raised md-primary" @click="auth">Login</md-button>
      </div>
    </md-content>
  </div>
</template>

<script>
export default {
  name: "Login.vue",
  data() {
    return {
      login: {
        email: "",
        password: "",
      },
    }
  },
  methods: {
    auth() {
      //do the login
    },
  },
}
</script>

<style lang="scss">
.centered-container {
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  height: 100vh;

  .title {
    text-align: center;
    margin-bottom: 30px;

    img {
      margin-bottom: 16px;
      max-width: 80px;
    }
  }

  //end title

  .actions {
    md-button {
      margin: 0;
    }
  }

  //end actions
  .form {
    margin-bottom: 60px;
  }

  .md-content {
    z-index: 1;
    padding: 40px;
    width: 100%;
    max-width: 400px;
    position: relative;
  }
}
</style>
