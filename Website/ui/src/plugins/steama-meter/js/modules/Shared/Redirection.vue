<template>
    <div>
        <md-dialog
                :md-active.sync="dialogActive"
                :md-close-on-esc="false"
                :md-click-outside-to-close="false"
        >
            <md-dialog-title>Redirection</md-dialog-title>
            <md-dialog-content>

                <div class="exclamation">
                        <span class="success-span">
                            <md-icon style="color: green">priority_high</md-icon>
                        </span>
                    <div class="md-layout-item md-size-100 exclamation-div">
                        <span>{{message}}</span>
                    </div>
                </div>
                <div class="md-layout-item md-size-100 exclamation-button-div">
                    <md-button role="button" type="button" class="md-raised md-primary" @click="redirect()">
                        Redirect
                    </md-button>
                </div>

            </md-dialog-content>

        </md-dialog>
    </div>
</template>

<script>
export default {
    name: 'Redirection',
    props: {
        redirectionUrl: {
            type: String,
            default: '/'
        },
        dialogActive: {
            type: Boolean,
            default: false
        },
        message: {
            type: String,
            default: 'API credentials not authenticated.'
        }
    },
    methods: {
        async redirect () {
            await this.$router.replace(this.redirectionUrl)
        }
    }
}
</script>

<style lang="scss">
    .success-span {
        font-size: large;
        font-weight: 700;
        color: green;
    }

    .exclamation-div {
        margin-top: 2% !important;
    }

    .exclamation {
        width: 100% !important;
        margin: auto;
        align-items: center;
        display: inline-grid;
        text-align: center;

    }

    .exclamation-div span {
        font-size: medium !important
    }

    .md-dialog-title {

        margin: auto !important;
    }

    .md-dialog-content {
        margin-top: 1.5rem !important;
    }

    .md-dialog-container {
        min-width: 490px !important;
        min-height: 240px !important;
    }

    .exclamation-button-div {
        margin-top: 2rem;
        text-align: right !important;
    }
</style>