<template>
  <div></div>
</template>

<script>
import { EventBus } from "./eventbus"

export default {
  name: "ConfirmationBox",
  props: {
    title: {
      required: true,
      type: String,
    },
  },
  data() {
    return {}
  },
  created() {
    EventBus.$on("show.confirm", this.showConfirmation)
  },
  methods: {
    showConfirmation(data = null) {
      this.$swal({
        type: "question",
        title: this.title,
        text: "Are you sure to do this action?",
        showCancelButton: true,
        confirmButtonText: "I'm sure",
        cancelButtonText: "Cancel",
      }).then((result) => {
        if (result.value) {
          this.$emit("confirmed", data)
        }
      })
    },
  },
}
</script>

<style scoped></style>
