<template>
  <div style="z-index: 9">
    <md-dialog :md-active.sync="show">
      <md-dialog-title>{{ title }}</md-dialog-title>
      <slot name="body"></slot>
      <md-dialog-actions>
        <md-button class="md-accent" @click="onClose">Close</md-button>
        <md-button
          v-if="show_confirm"
          class="md-primary btn-lg"
          @click="onSave"
          v-text="confirm_text"
        ></md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
export default {
  name: "modal",
  props: {
    title: {
      type: String,
      required: true,
    },
    show: {
      type: Boolean,
      default: false,
      required: true,
    },
    confirm_text: {
      type: String,
      default: "Save",
    },
    show_confirm: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      selectedCountry: null,
      countries: [
        "Algeria",
        "Argentina",
        "Brazil",
        "Canada",
        "Italy",
        "Japan",
        "United Kingdom",
        "United States",
      ],
    }
  },
  methods: {
    onSave() {
      this.$emit("onSave", null)
    },
    onClose() {
      this.$emit("onClose")
    },
  },
}
</script>

<style lang="scss">
.md-menu-content {
  z-index: 11 !important;
}

.comment-box {
  border-bottom: 1px dotted #ccc;
  padding: 5px;
  margin-bottom: 5px;
}

.modal-mask {
  position: fixed;
  z-index: 1059;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: table;
  transition: opacity 0.3s ease;
}

.modal-wrapper {
  display: table-cell;
  vertical-align: middle;
}

.modal-container {
  width: 45%;
  margin: 0px auto;
  padding: 20px 30px;
  background-color: #fff;
  border-radius: 2px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
  transition: all 0.3s ease;
  font-family: Helvetica, Arial, sans-serif;
  overflow-y: scroll;
}

@media only screen and (max-width: 600px) {
  .modal-container {
    width: 90% !important;
    height: 90% !important;
    max-height: 90% !important;
  }
}

@media only screen and (max-width: 1024px) and (min-width: 601px) {
  .modal-container {
    width: 70% !important;
    height: 80% !important;
    max-height: 90% !important;
  }
}

@media only screen and (max-width: 1280px) and (min-width: 1025px) {
  .modal-container {
    width: 60% !important;
    height: 85% !important;
    max-height: 95% !important;
  }
}

.modal-header h3 {
  margin-top: 0;
  color: #42b983;
}

.modal-body {
  margin: 20px 0;
}

.modal-default-button {
  float: right;
}

/*
     * The following styles are auto-applied to elements with
     * transition="modal" when their visibility is toggled
     * by Vue.js.
     *
     * You can easily play with the modal transition by editing
     * these styles.
     */

.modal-enter {
  opacity: 0;
}

.modal-leave-active {
  opacity: 0;
}

.modal-enter .modal-container,
.modal-leave-active .modal-container {
  -webkit-transform: scale(1.1);
  transform: scale(1.1);
}

.red {
  background-color: red;
  color: white;
}

.purple {
  background-color: purple;
  color: white;
}

.lime {
  background-color: rgb(191, 230, 31);
}
</style>
