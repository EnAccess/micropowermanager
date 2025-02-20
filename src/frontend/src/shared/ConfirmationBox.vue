
<template>
  <md-dialog :md-active.sync="showDialog">
    <md-dialog-title>{{ title }}</md-dialog-title>

    <md-dialog-content>
      <p>{{ message }}</p>
      <div v-if="showCheckbox" class="md-layout md-alignment-center-left">
        <md-checkbox v-model="isChecked">{{ checkboxLabel }}</md-checkbox>
      </div>
    </md-dialog-content>

    <md-dialog-actions>
      <md-button class="md-accent" @click="onCancel">
        {{ cancelText }}
      </md-button>
      <md-button class="md-primary" @click="onConfirm" :disabled="showCheckbox && !isChecked">
        {{ confirmText }}
      </md-button>
    </md-dialog-actions>
  </md-dialog>
</template>

<script>
export default {
  name: 'ConfirmationBox',
  props: {
    title: {
      type: String,
      required: true
    },
    message: {
      type: String,
      default: ''
    },
    showCheckbox: {
      type: Boolean,
      default: false
    },
    checkboxLabel: {
      type: String,
      default: ''
    },
    confirmText: {
      type: String,
      default: 'Confirm'
    },
    cancelText: {
      type: String,
      default: 'Cancel'
    }
  },
  data() {
    return {
      showDialog: false,
      isChecked: false,
      resolvePromise: null
    }
  },
  methods: {
    show() {
      this.showDialog = true
      this.isChecked = false
      return new Promise((resolve) => {
        this.resolvePromise = resolve
      })
    },
    onConfirm() {
      this.showDialog = false
      if (this.resolvePromise) {
        this.resolvePromise({ confirmed: true, checked: this.isChecked })
      }
    },
    onCancel() {
      this.showDialog = false
      if (this.resolvePromise) {
        this.resolvePromise({ confirmed: false, checked: this.isChecked })
      }
    }
  }
}
</script>