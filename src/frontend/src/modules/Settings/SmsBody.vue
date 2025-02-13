<template>
  <div>
    <md-card>
      <md-card-header>
        {{ $tc("phrases." + smsBody.title) }}
      </md-card-header>
      <md-card-content class="md-layout md-gutter md-size-100">
        <div class="md-layout-item md-size-70">
          <md-field :class="{ 'md-invalid': errors.has('body') }">
            <md-textarea
              :placeholder="smsBody.placeholder"
              v-model="smsBody.body"
              id="body"
              name="body"
              md-autogrow
              @keydown.native="getLastBody()"
              @keyup.native="checkBody($event)"
              v-validate="'required'"
            ></md-textarea>
            <span class="md-error">{{ errors.first("body") }}</span>
          </md-field>
        </div>
        <div class="md-layout-item md-size-30">
          <div v-if="smsBody.variables[0] !== ''">
            <md-chip
              v-for="(variable, index) in smsBody.variables"
              :key="index"
              class="md-accent"
              md-clickable
              @click="selectVariable($event)"
            >
              {{ variable }}
            </md-chip>
          </div>
        </div>
        <div class="md-layout-item placeholder-message-area">
          {{ smsVariableDefaultValueService.shownMessage }}
        </div>
      </md-card-content>
    </md-card>
  </div>
</template>

<script>
import { SmsVariableDefaultValueService } from "@/services/SmsVariableDefaultValueService"
import { notify } from "@/mixins/notify"

export default {
  name: "SmsBody",
  mixins: [notify],
  props: {
    smsBody: {
      default: null,
    },
    smsVariableDefaultValues: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      lastWords: [],
      constantVariables: [],
      regExp: /([^\s]+)+/g,
      smsVariableDefaultValueService: new SmsVariableDefaultValueService(),
    }
  },
  mounted() {
    this.constantVariables = this.smsBody.variables.map((e) => {
      return e.replace(/[^a-zA-Z0-9]/g, "")
    })
    setTimeout(() => {
      this.prepareShownMessage()
    }, 100)
  },
  methods: {
    selectVariable(tag) {
      let tagName = tag.currentTarget.innerText
      this.smsBody.body += " " + "[" + tagName + "]"
      this.prepareShownMessage()
    },
    checkBody(evt) {
      if (evt.keyCode === 8) {
        let newWords = this.smsBody.body.match(this.regExp)
        if (!newWords) {
          this.prepareShownMessage()
          return
        }
        let except = newWords.filter(
          (e) => !this.lastWords.find((a) => e === a),
        )[0]
        if (!except) {
          this.prepareShownMessage()
          return
        }
        if (
          !this.constantVariables.includes(except.replace(/[^a-zA-Z0-9]/g, ""))
        ) {
          this.prepareShownMessage()
          return
        }
        let bodyArray = this.smsBody.body.match(this.regExp)
        this.smsBody.body = ""
        for (let i in bodyArray) {
          if (bodyArray[i] !== except) {
            this.smsBody.body += " " + bodyArray[i]
          }
        }
      }
      this.prepareShownMessage()
    },
    getLastBody() {
      this.lastWords = this.smsBody.body.match(this.regExp)
    },
    prepareShownMessage() {
      this.smsVariableDefaultValueService.prepareShownMessage(
        this.smsBody.body,
        this.smsVariableDefaultValues,
      )
    },
    async validateBody() {
      this.smsBody.validation = await this.$validator.validateAll(this.tabName)
    },
  },
}
</script>

<style scoped>
.placeholder-message-area {
  padding: 20px;
  background-color: #dfe9f3;
  margin: 10px;
  d-webkit-border-radius: 16px;
  -moz-border-radius: 16px;
  border-radius: 16px;
}
</style>
