<template>
  <widget
    :title="$tc('phrases.smsHistory') + ' (' + smses.length + ')'"
    color="green"
    :subscriber="subscriber"
    :button="true"
    :empty-state-create-button="true"
    @widgetAction="hideEmptyStateArea"
  >
    <div>
      <md-content
        class="md-scrollbar chat-body chat-body-scroll"
        ref="chat"
        id="chat-body"
      >
        <md-list class="md-triple-line">
          <md-list-item
            v-for="sms in smses"
            :key="sms.id"
            class="md-scrollbar"
            :class="sms.direction === 0 ? 'incomming' : ''"
          >
            <md-icon v-if="sms.direction !== 0">textsms</md-icon>
            <md-icon v-else>mark_email_unread</md-icon>

            <div class="md-list-item-text md-size-100">
              <div class="md-layout">
                <div class="md-layout-item md-gutter md-size-100">
                  <div
                    class="md-layout-item md-size-5"
                    style="float: left; font-weight: bold"
                  >
                    <small>
                      <md-icon>person</md-icon>
                    </small>
                  </div>
                  <div class="md-layout-item md-size-95 sms-body">
                    <a
                      v-if="sms.direction === 0"
                      href="javascript:void(0);"
                      class="username"
                    >
                      {{ sms.personName }}
                    </a>
                    <a v-else href="javascript:void(0);" class="username">
                      {{ $tc("words.system") }}
                    </a>
                  </div>
                </div>
                <div class="md-layout-item md-size-100">
                  <span>
                    <small>
                      {{ formatDate(sms.created_at) }} -
                      {{ getTimeAgo(sms.created_at) }}
                    </small>
                  </span>
                </div>
                <div class="md-layout-item md-size-100">
                  <p style="white-space: pre-line">
                    {{ sms.body }}
                  </p>
                </div>
              </div>
            </div>
          </md-list-item>
        </md-list>
        <div class="md-layout md-gutter md-size-100" style="margin: 2vh">
          <div class="md-layout-item md-size-100">
            <md-field>
              <md-textarea
                :placeholder="$tc('phrases.writeMessage')"
                v-model="message"
              ></md-textarea>
            </md-field>
          </div>
          <div class="md-layout-item md-size-100">
            <div class="container">
              <div class="content"></div>
              <md-button
                type="submit"
                class="md-primary md-raised md-alignment-center-right"
                @click="sendSms"
              >
                {{ $tc("words.send") }}
              </md-button>
            </div>
          </div>
        </div>
      </md-content>
    </div>
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { EventBus } from "@/shared/eventbus"
import moment from "moment"
import { SmsService } from "@/services/SmsService"
import { mapGetters } from "vuex"

import { notify } from "@/mixins/notify"

export default {
  name: "SmsHistory",
  mixins: [notify],
  components: { Widget },
  props: {
    personId: {
      type: String,
      required: true,
    },
    personName: {
      type: String,
      required: true,
    },
  },
  computed: {
    ...mapGetters({
      authUser: "auth/getAuthenticateUser",
    }),
  },
  mounted() {
    this.getSmsList()
  },
  data() {
    return {
      smsService: new SmsService(),
      smses: [],
      message: "",
      subscriber: "customer-sms-history",
    }
  },
  methods: {
    hideEmptyStateArea() {
      EventBus.$emit("hideEmptyStateArea", this.subscriber)
    },
    getTimeAgo(date) {
      return moment(date).fromNow()
    },
    formatDate(date) {
      let d = new Date(date)
      return d.toLocaleDateString()
    },
    getSmsList() {
      this.smsService.getList(this.personId).then((response) => {
        this.smses = response
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.smses.length,
        )
        if (this.smses.length) {
          this.scrollDown()
        }
      })
    },
    async sendSms() {
      if (this.message.length <= 3) {
        this.alertNotify("warn", this.$tc("phrases.messageNotify"))
        return
      }
      try {
        const {
          data: { data },
        } = await this.smsService.sendToPerson(
          this.message,
          this.personId,
          this.authUser.id,
        )
        this.alertNotify("success", this.$tc("words.successful"))
        this.smses.push(data)
        this.message = ""
        this.scrollDown()
      } catch (e) {
        console.log(e)
      }
    },

    scrollDown() {
      let parent = this
      setTimeout(function () {
        let chat = parent.$refs.chat
        chat.scrollTop = chat.scrollHeight
      }, 1000)
    },
  },
}
</script>

<style scoped>
.md-content {
  max-height: 400px;
  overflow: auto;
}

.sms-body {
  float: right;
  font-weight: bolder;
  margin-top: 5px;
}

.chat-body-scroll {
  overflow-y: scroll !important;
}

.md-list {
  max-width: 100%;
  display: inline-block;
  vertical-align: top;
  border: 1px solid rgba(#000, 0.12);
}

.incomming {
  margin-left: 5px !important;
  padding: 10px;
  background-color: rgba(7, 249, 127, 0.23);
}

.container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
}

.content {
  flex-grow: 1;
}
</style>
