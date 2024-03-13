<template>
    <div class="row">
        <widget :title="$tc('words.profile')">
            <form class="md-layout" data-vv-scope="address">
                <md-card class="md-layout-item md-size-100">
                    <md-card-content>
                        <div class="md-layout md-gutter">
                            <div
                                class="md-layout-item md-size-50 md-small-size-100"
                            >
                                <md-field
                                    :class="{
                                        'md-invalid': errors.has(
                                            $tc('words.name'),
                                        ),
                                    }"
                                >
                                    <label>{{ $tc('words.name') }}</label>
                                    <md-input
                                        v-model="userService.user.name"
                                        v-validate="'required|min:2|max:20'"
                                        :name="$tc('words.name')"
                                        id="name"
                                    />
                                    <md-icon>create</md-icon>
                                    <span class="md-error">
                                        {{ errors.first($tc('words.name')) }}
                                    </span>
                                </md-field>
                            </div>

                            <div
                                class="md-layout-item md-size-50 md-small-size-100"
                            >
                                <md-field>
                                    <label>{{ $tc('words.email') }}</label>
                                    <md-input
                                        readonly
                                        v-model="userService.user.email"
                                        name="email"
                                        id="email"
                                    />
                                    <md-icon>sms</md-icon>
                                </md-field>
                            </div>
                            <div
                                class="md-layout-item md-size-50 md-small-size-100"
                            >
                                <md-field>
                                    <label>{{ $tc('words.phone') }}</label>
                                    <md-input
                                        type="number"
                                        v-model="userService.user.phone"
                                    />
                                    <md-icon>phone</md-icon>
                                </md-field>
                            </div>
                            <div
                                class="md-layout-item md-size-50 md-small-size-100"
                            >
                                <md-field>
                                    <label>{{ $tc('words.street') }}</label>
                                    <md-input
                                        v-model="userService.user.street"
                                    />
                                    <md-icon>contacts</md-icon>
                                </md-field>
                            </div>

                            <div
                                class="md-layout-item md-size-50 md-small-size-100"
                            >
                                <md-field
                                    :class="{
                                        'md-invalid': errors.has(
                                            $tc('words.city'),
                                        ),
                                    }"
                                >
                                    <label for="city">
                                        {{ $tc('words.city') }}
                                    </label>
                                    <md-select
                                        v-model="selectedCity"
                                        required
                                        :name="$tc('words.city')"
                                        id="city"
                                        v-validate.initial="'required'"
                                        :class="{
                                            'md-invalid': errors.has(
                                                $tc('words.city'),
                                            ),
                                        }"
                                    >
                                        <md-option
                                            v-for="c in cityService.cities"
                                            :key="c.id"
                                            :value="c.id"
                                        >
                                            {{ c.name }}
                                        </md-option>
                                    </md-select>
                                    <span class="md-error">
                                        {{ errors.first($tc('words.city')) }}
                                    </span>
                                </md-field>
                            </div>
                            <div class="md-layout-item md-size-100">
                                <md-button
                                    class="md-primary save-button"
                                    @click="updateDetails()"
                                >
                                    {{ $tc('words.save') }}
                                </md-button>
                                <md-button
                                    class="md-primary change-button"
                                    @click="modalVisibility = true"
                                >
                                    {{ $tc('phrases.changePassword') }}
                                </md-button>
                            </div>
                        </div>
                    </md-card-content>
                </md-card>
                <md-progress-bar md-mode="indeterminate" v-if="sending" />
            </form>
        </widget>

        <md-dialog :md-active.sync="modalVisibility">
            <md-dialog-title>
                {{ $tc('phrases.changePassword') }}
            </md-dialog-title>
            <md-dialog-content>
                <div class="password-edit-container">
                    <form
                        class="md-layout"
                        data-vv-scope="Change-Password-Form"
                    >
                        <md-field
                            :class="{
                                'md-invalid': errors.has(
                                    'Change-Password-Form.password',
                                ),
                            }"
                        >
                            <label for="password">
                                {{ $tc('words.password') }}
                            </label>
                            <md-input
                                type="password"
                                name="password"
                                id="password"
                                v-validate="'required|min:3|max:15'"
                                v-model="passwordService.user.password"
                                ref="passwordRef"
                            />
                            <span class="md-error">
                                {{
                                    errors.first(
                                        'Change-Password-Form.password',
                                    )
                                }}
                            </span>
                        </md-field>

                        <md-field
                            :class="{
                                'md-invalid': errors.has(
                                    'Change-Password-Form.confirmPassword',
                                ),
                            }"
                        >
                            <label for="confirmPassword">
                                {{ $tc('phrases.confirmPassword') }}
                            </label>
                            <md-input
                                type="password"
                                name="confirmPassword"
                                id="confirmPassword"
                                v-model="passwordService.user.confirmPassword"
                                v-validate="
                                    'required|confirmed:$passwordRef|min:3|max:15'
                                "
                            />
                            <span class="md-error">
                                {{
                                    errors.first(
                                        'Change-Password-Form.confirmPassword',
                                    )
                                }}
                            </span>
                        </md-field>

                        <md-progress-bar
                            md-mode="indeterminate"
                            v-if="sending"
                        />
                    </form>
                </div>
            </md-dialog-content>

            <md-dialog-actions>
                <md-button class="md-raised md-primary" @click="changePassword">
                    {{ $tc('words.save') }}
                </md-button>
                <md-button @click="modalVisibility = false">
                    {{ $tc('words.close') }}
                </md-button>
            </md-dialog-actions>
        </md-dialog>
    </div>
</template>

<script>
import Widget from '../../shared/widget'
import { UserService } from '@/services/UserService'
import { CityService } from '@/services/CityService'
import { UserPasswordService } from '@/services/UserPasswordService'
export default {
    name: 'Profile',
    components: { Widget },
    data() {
        return {
            sending: false,
            modalVisibility: false,
            selectedCity: '',
            userService: new UserService(),
            cityService: new CityService(),
            passwordService: new UserPasswordService(),
        }
    },
    mounted() {
        this.getCities()
        this.getUser()
    },
    methods: {
        async getCities() {
            try {
                await this.cityService.getCities()
            } catch (error) {
                this.alertNotify('error', error.message)
            }
        },
        async getUser() {
            try {
                await this.userService.get(
                    this.$store.getters['auth/authenticationService']
                        .authenticateUser.id,
                )
                if (this.userService.user.cityId !== undefined) {
                    this.selectedCity = this.cityService.cities
                        .filter((x) => x.id === this.userService.user.cityId)
                        .map((x) => x.id)[0]
                }
            } catch (error) {
                this.alertNotify('error', error.message)
            }
        },
        async updateDetails() {
            this.sending = true
            let validation = await this.$validator.validateAll('address')
            if (!validation) {
                return
            }
            if (this.selectedCity !== undefined) {
                this.userService.user.city_id = this.selectedCity
            }
            try {
                await this.userService.update()
                this.alertNotify('success', this.$tc('words.profile', 2))
            } catch (error) {
                this.alertNotify('error', error)
            }
            this.sending = false
        },
        async changePassword() {
            this.sending = true
            let validation = await this.$validator.validateAll(
                'Change-Password-Form',
            )
            if (!validation) {
                return
            }
            try {
                await this.passwordService.update(this.userService.user.id)
                this.alertNotify('success', this.$tc('words.profile', 2))
                this.closeModal()
            } catch (error) {
                this.alertNotify('error', error)
                this.closeModal()
            }
            this.sending = false
        },
        closeModal() {
            this.modalVisibility = false
        },
        alertNotify(type, message, title = null) {
            if (title == null) {
                title =
                    type.toString().charAt(0).toUpperCase() +
                    type.toString().slice(1)
            }
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message,
            })
        },
    },
}
</script>

<style scoped>
.save-button {
    background-color: #325932 !important;
    color: #fefefe !important;
    float: right;
}
.change-button {
    background-color: #4f4e94 !important;
    color: #fefefe !important;
    float: right;
}
.password-edit-container {
    padding: 1rem;
}
</style>
