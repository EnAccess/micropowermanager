<template>
  <div>
    <div class="wide-screen-sms-list md-layout md-gutter">
      <div
        class="md-layout-item md-size-30 md-medium-size-40 md-small-size-100"
        v-if="checkScreen('numberList')"
      >
        <widget :subscriber="subscriber" :paginator="smsService.paginator">
          <div class="sticky">
            <md-field>
              <md-input
                v-model="filterNumber"
                type="text"
                class="form-control"
                :placeholder="$tc('words.search')"
              ></md-input>
            </md-field>
          </div>
          <div class="sms-scrollable">
            <md-table>
              <md-table-row
                v-for="sms in numberList"
                :key="sms.number"
                style="cursor: pointer"
                @click="smsDetail(sms.number)"
              >
                <md-table-cell
                  :class="sms.number === selectedNumber ? 'active' : ''"
                >
                  <div class="md-layout md-gutter">
                    <div class="md-layout-item md-size-30" v-if="sms.owner">
                      <img
                        :data-letters="sms.owner.name[0] + sms.owner.surname[0]"
                        src=""
                        alt=""
                      />
                    </div>
                    <div class="md-layout-item md-size-30" v-else>
                      <md-icon class="person-icon">person</md-icon>
                    </div>
                    <div class="md-layout-item md-size-55">
                      <div class="md-layout md-layout-item md-size-100">
                        <div
                          class="md-layout-item md-size-100 sms-owner"
                          v-if="sms.owner"
                        >
                          {{ sms.owner.name }}
                          {{ sms.owner.surname }}
                        </div>
                        <div class="md-layout-item md-size-100" v-else>---</div>
                        <div class="md-layout-item md-size-100">
                          <small>
                            {{ sms.number }}
                          </small>
                        </div>
                      </div>
                    </div>
                    <div class="md-layout-item md-size-15">
                      <small class="sms-total">
                        {{ sms.total }}
                      </small>
                    </div>
                  </div>
                </md-table-cell>
              </md-table-row>
            </md-table>
          </div>
          <!-- Sms Number List -->
        </widget>
      </div>
      <div
        class="md-layout-item md-size-70 md-medium-size-60 md-small-size-100"
        v-if="checkScreen('detail')"
      >
        <widget>
          <div class="sticky sms-detail-head">
            <div class="md-layout-item md-layout md-gutter">
              <div class="md-layout-item md-size-95">
                <md-icon>perm_phone_msg</md-icon>
                {{ selectedNumber }}
              </div>
              <div class="md-layout-item md-size-5">
                <md-icon v-if="!isMobile">sms</md-icon>
                <md-button
                  class="md-icon-button"
                  v-if="isMobile"
                  @click="showNumberList = true"
                >
                  <md-icon>reply</md-icon>
                </md-button>
              </div>
            </div>
            <hr />
          </div>
          <div class="sms-detail-scrollable">
            <div class="md-layout" v-for="sms in list" :key="sms.id">
              <div class="md-layout-item md-layout md-size-100 md-gutter">
                <div
                  class="md-layout-item md-size-60"
                  v-if="sms.direction === 0"
                >
                  <div class="md-layout-item md-layout md-gutter">
                    <div class="sms-body-triangle left-arrow"></div>
                    <div class="md-layout-item sms-body sms-body-left">
                      {{ sms.body }}
                    </div>
                  </div>
                </div>
                <div class="md-layout-item md-size-40"></div>
                <div
                  class="md-layout-item md-size-60"
                  v-if="sms.direction === 1"
                >
                  <div class="md-layout-item md-layout md-gutter">
                    <div class="md-layout-item sms-body">
                      {{ sms.body }}
                    </div>
                    <div class="sms-body-triangle right-arrow"></div>
                  </div>
                </div>

                <div class="md-layout-item md-size-100">
                  <small
                    :class="
                      sms.direction === 1
                        ? 'created-date-right'
                        : 'created-date-left'
                    "
                  >
                    <md-icon>schedule</md-icon>
                    {{ formatDate(sms.created_at) }} -
                    {{ getTimeAgo(sms.created_at) }}
                  </small>
                </div>
              </div>
            </div>
          </div>
          <div class="md-layout md-gutter message-area">
            <div class="md-layout-item md-size-100">
              <md-field :class="{ 'md-invalid': errors.has('message') }">
                <label>{{ $tc("phrases.messageText") }}</label>
                <md-textarea
                  v-model="message"
                  style="min-height: 75px !important"
                  id="message"
                  name="message"
                  v-validate="'required|max:160|min:3'"
                />
                <span class="md-error">
                  {{ errors.first("message") }}
                </span>
              </md-field>
              <md-progress-bar md-mode="indeterminate" v-if="loading" />
            </div>
            <div class="md-layout-item md-size-100">
              <md-button
                @click="sendSms"
                :disabled="loading || selectedNumber === ''"
                class="md-raised md-primary send-button"
              >
                {{ $tc("words.send") }}
              </md-button>
            </div>
          </div>
        </widget>
      </div>
      <!-- Sms Detail  -->
    </div>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { EventBus } from "@/shared/eventbus"
import { SmsService } from "@/services/SmsService"
import moment from "moment"
import { notify } from "@/mixins/notify"

const debounce = require("debounce")

export default {
  name: "List",
  mixins: [notify],
  components: { Widget },
  watch: {
    filterNumber: debounce(function () {
      if (this.filterNumber.length > 0) {
        this.searchSms(this.filterNumber)
      }
    }, 1000),
  },

  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    this.senderId =
      this.$store.getters["auth/authenticationService"].authenticateUser.id
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  data() {
    return {
      smsService: new SmsService(),
      list: [],
      numberList: [],
      filterNumber: "",
      subscriber: "smsList",
      selectedNumber: "",
      showNumberList: true,
      senderId: "",
      message: "",
      headers: [],
      tableName: "SMS",
      loading: false,
      windowWidth: 0,
    }
  },
  computed: {
    isMobile() {
      return this.$store.getters["resolution/getDevice"]
    },
  },
  methods: {
    checkScreen(type) {
      let x
      if (this.isMobile === false) {
        x = true
      } else {
        if (this.showNumberList === true) {
          if (type === "numberList") {
            x = true
          }
          if (type === "detail") {
            x = false
          }
        } else {
          if (type === "numberList") {
            x = false
          }
          if (type === "detail") {
            x = true
          }
        }
      }

      return x
    },
    getTimeAgo(date) {
      return moment(date).fromNow()
    },
    formatDate(date) {
      let d = new Date(date)
      return d.toLocaleDateString()
    },
    async reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.numberList = await this.smsService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.numberList.length,
      )
    },
    async loadList() {
      this.list = []
      this.numberList = []
      try {
        this.numberList = await this.smsService.getList()
        if (this.numberList.length > 0)
          this.list = this.smsDetail(this.numberList[0].number)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async smsDetail(phone) {
      this.showNumberList = false
      this.selectedNumber = phone
      this.list = await this.smsService.getDetail(phone)
    },
    async sendSms() {
      const validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      try {
        this.loading = true
        await this.smsService.sendToNumber(
          "person",
          this.message,
          this.selectedNumber,
          this.senderId,
        )
        this.loading = false
        this.alertNotify("success", this.$tc("phrases.smsListNotify", 2))
        this.message = ""
        await this.smsDetail(this.selectedNumber)
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
      this.$validator.reset()
    },
    searchSms(text) {
      this.numberList = this.smsService.searchSms(text)
    },
  },
}
</script>

<style scoped>
[data-letters]:before {
  content: attr(data-letters);
  display: inline-block;
  font-size: 1em;
  width: 2.5em;
  height: 2.5em;
  line-height: 2.5em;
  text-align: center;
  border-radius: 50%;
  background: #313131;
  vertical-align: middle;
  margin-right: 1em;
  color: white;
}

.person-icon {
  width: 1.3em;
  height: 1.3em;
  text-align: center;
  border-radius: 50%;
  background: #313131;
  vertical-align: middle;
  margin-right: 1em;
  color: white !important;
}

td.active {
  background-color: #e2f3fd !important;
}

.sticky {
  position: sticky;
  top: 0;
  height: 20% !important;
}

.sms-detail-head {
  margin-top: 23px;
}

.sms-scrollable {
  overflow: auto;
  max-height: 73vh;
}

.sms-detail-scrollable {
  overflow: auto;
  height: 60vh;
  max-height: 60vh;
}

.created-date-right {
  float: right;
  margin-right: 5px;
}

.created-date-left {
  float: left;
  margin-left: 5px;
}

.sms-body {
  padding: 20px;
  background-color: #e2f3fd;
  margin: 10px;
  d-webkit-border-radius: 10px;
  -moz-border-radius: 10px;
  border-radius: 10px;
}

.sms-body-left {
  background-color: #ffebee !important;
}

.sms-body-triangle {
  width: 0;
  height: 0;
  border-top: 8px solid transparent;
  border-bottom: 8px solid transparent;
  margin-top: 40px;
}

.right-arrow {
  border-left: 8px solid #e2f3fd;
  margin-left: -10px;
  margin-right: -20px;
}

.left-arrow {
  border-right: 8px solid #ffebee;
  margin-right: -10px;
  margin-left: -20px;
}

.send-button {
  width: 10vw;
  right: 0;
  float: right;
}

.message-area {
  margin-top: 2vh;
}

.sms-total {
  position: absolute;
  right: 1vw;
  background-color: #f2622d;
  text-align: center;
  border-radius: 50%;
  vertical-align: middle;
  color: whitesmoke;
  width: 1.5em;
  height: 1.5em;
}

.sms-owner {
  font-weight: 500;
}
</style>
