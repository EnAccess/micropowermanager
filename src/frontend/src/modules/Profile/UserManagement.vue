<template>
  <div>
    <new-user
      @newUserClosed="showNewUser = false"
      :showNewUser="showNewUser"
      :user="userService.user"
      @createUser="createUser"
    ></new-user>
    <edit-user
      @editUserClosed="showEditUser = false"
      :showEditUser="showEditUser"
      :user="userService.user"
      @updateUser="updateUser"
      :cities="cities"
    />
    <widget
      :title="$tc('phrases.userManagement')"
      :button-text="$tc('phrases.newUser')"
      :button="true"
      @widgetAction="showNewUser = true"
      :subscriber="subscriber"
      :paginator="userService.paginator"
      :key="resetKey"
    >
      <div class="md-layout">
        <div class="md-layout md-gutter">
          <div
            class="md-layout-item md-size-100 md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
          >
            <md-table md-card style="margin-left: 0">
              <md-table-row>
                <md-table-head>
                  {{ $tc("words.id") }}
                </md-table-head>
                <md-table-head>
                  {{ $tc("words.name") }}
                </md-table-head>
                <md-table-head>
                  {{ $tc("words.email") }}
                </md-table-head>
                <md-table-head>
                  {{ $tc("words.phone") }}
                </md-table-head>
              </md-table-row>

              <md-table-row
                @click="userDetail(user)"
                v-for="user in userService.users"
                :key="user.id"
                style="cursor: pointer"
              >
                <md-table-cell>{{ user.id }}</md-table-cell>
                <md-table-cell>{{ user.name }}</md-table-cell>
                <md-table-cell>{{ user.email }}</md-table-cell>
                <md-table-cell>{{ user.phone }}</md-table-cell>
              </md-table-row>
            </md-table>
          </div>
        </div>
      </div>
    </widget>
    <md-progress-bar md-mode="indeterminate" v-if="sending" />
  </div>
</template>
<script>
import Widget from "../../shared/widget"
import { EventBus } from "@/shared/eventbus"
import { UserService } from "@/services/UserService"
import { CityService } from "@/services/CityService"
import NewUser from "./NewUser"
import EditUser from "./EditUser"
import { notify } from "@/mixins/notify"

export default {
  name: "ProfileManagement",
  mixins: [notify],
  components: { Widget, NewUser, EditUser },
  data() {
    return {
      subscriber: "user-management",
      sending: false,
      showEditUser: false,
      selectedCity: 0,
      userService: new UserService(),
      cityService: new CityService(),
      userId: 0,
      showNewUser: false,
      resetKey: 1,
      cities: [],
    }
  },
  created() {
    this.getCities()
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("newUserCreated", () => this.resetKey++)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded")
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) {
        return
      }
      this.userService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.userService.users.length,
      )
    },
    async getCities() {
      try {
        await this.cityService.getCities()
        this.cities = this.cityService.cities
      } catch (error) {
        this.alertNotify("error", error.message)
      }
    },
    async userDetail(user) {
      try {
        await this.userService.get(user.id)
        this.showEditUser = true
      } catch (error) {
        this.alertNotify("error", error)
      }
    },
    async updateUser(user) {
      this.sending = true
      if (user.cityId !== 0) {
        this.userService.user.cityId = user.cityId
      }
      try {
        await this.userService.update()
        this.alertNotify("success", this.$tc("words.profile", 2))
        this.showEditUser = false
        this.resetKey++
      } catch (error) {
        this.alertNotify("error", error)
      }
      this.sending = false
    },
    async createUser() {
      this.sending = true
      try {
        await this.userService.create()
        this.alertNotify("success", this.$tc("phrases.newUser", 2))
        this.showNewUser = false
        this.resetKey++
      } catch (error) {
        this.alertNotify("error", error.message)
      }
      this.sending = false
    },
  },
}
</script>
