<template>
  <widget
    :title="$tc('phrases.smsHistory') + ' (' + smses.length + ')'"
    color="primary"
    :subscriber="subscriber"
    :button="true"
    :empty-state-create-button="true"
    @widgetAction="hideEmptyStateArea"
  >
    <div>
      <md-content class="chat-body chat-body-scroll" ref="chat" id="chat-body">
        <md-list class="md-triple-line">
          <md-list-item
            v-for="sms in smses"
            :key="sms.id"
            :class="sms.direction === 0 ? 'incomming' : ''"
          >
            <md-icon v-if="sms.direction !== 0">textsms</md-icon>
            <md-icon v-else>mark_email_unread</md-icon>

            <div class="md-list-item-text md-size-100">
              <div class="md-layout">
                <div class="md-layout-item md-gutter md-size-100 user-header">
                  <div class="user-icon">
                    <md-icon>person</md-icon>
                  </div>
                  <div class="md-layout-item md-size-95 username-header">
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
                      <sms-delivery-status
                        class="sms-status"
                        v-if="sms.direction !== 0"
                        :status="sms.status"
                        :error-message="sms.error_message"
                        :gateway-id="sms.gateway_id"
                        :attempts="sms.delivery_attempts"
                      />
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
            <whatsapp-template-fields v-model="whatsappTemplate" />
            <md-field v-if="whatsappTemplate.templateId === null">
              <md-textarea
                :placeholder="$tc('phrases.writeMessage')"
                v-model="message"
              ></md-textarea>
            </md-field>
          </div>
          <div class="md-layout-item md-size-100">
            <md-progress-bar md-mode="indeterminate" v-if="isSending" />
            <div class="container">
              <div class="content"></div>
              <md-button
                type="submit"
                class="md-primary md-raised md-alignment-center-right"
                :disabled="isSending"
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
import moment from "moment"
import { mapGetters } from "vuex"

import { notify } from "@/mixins/notify.js"
import { SmsService } from "@/services/SmsService.js"
import { EventBus } from "@/shared/eventbus.js"
import SmsDeliveryStatus from "@/shared/SmsDeliveryStatus.vue"
import WhatsappTemplateFields from "@/shared/WhatsappTemplateFields.vue"
import Widget from "@/shared/Widget.vue"

export default {
  name: "SmsHistory",
  mixins: [notify],
  components: { SmsDeliveryStatus, WhatsappTemplateFields, Widget },
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
      whatsappTemplate: { templateId: null, values: {}, isComplete: true },
      isSending: false,
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
      // A second click while the request is running would send the message again.
      if (this.isSending) {
        return
      }
      const isTemplate = this.whatsappTemplate.templateId !== null
      if (!isTemplate && this.message.length <= 3) {
        this.alertNotify("warn", this.$tc("phrases.messageNotify"))
        return
      }
      if (!this.whatsappTemplate.isComplete) {
        this.alertNotify(
          "warn",
          this.$t("phrases.whatsappTemplatesFillEveryField"),
        )
        return
      }
      this.isSending = true
      try {
        const {
          data: { data },
        } = await this.smsService.sendToPerson(
          isTemplate ? "" : this.message,
          this.personId,
          this.authUser.id,
          this.whatsappTemplate,
        )
        this.alertNotify("success", this.$tc("words.successful"))
        this.smses.push(data)
        this.message = ""
        this.whatsappTemplate = {
          templateId: null,
          values: {},
          isComplete: true,
        }
        this.scrollDown()
      } catch (e) {
        this.alertNotify("error", e.message)
        console.log(e)
      } finally {
        this.isSending = false
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

<style scoped lang="scss">
.md-content {
  max-height: 400px;
  overflow: auto;
}

.sms-body {
  float: right;
  font-weight: bolder;
  margin-top: 5px;
}

.user-header {
  display: flex;
  align-items: center;
}

.user-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  margin-right: 8px;
}

.username-header {
  display: inline-flex;
  align-items: center;
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

// Vue Material stretches every node inside `.md-list-item-text` to full width and
// clips the overflow. The delivery status is an inline-flex box, so it inherits the
// full width, lands past the end of the timestamp line and gets clipped from view.
::v-deep .md-list-item-text {
  overflow: visible;

  small {
    white-space: normal;
  }

  .sms-status {
    width: auto;
    overflow: visible;

    * {
      overflow: visible;
      text-overflow: clip;
    }

    // A full-width label cannot share a flex line with the icon, so it wraps
    // underneath instead of sitting beside it.
    button,
    span {
      width: auto;
    }
  }
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
