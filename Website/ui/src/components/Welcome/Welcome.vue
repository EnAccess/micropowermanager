<template>
    <div class="welcome">
        <div class="content">
            <div class="title">
                Welcome to the <span class="title highlight">MicroPowerManager</span>cloud version
            </div>

            <p class="cloud-description">
                This is the cloud version of the MicroPowerManager. It is a web application that allows you to manage your
                MiniGrids easily.
            </p>
            <div class="router-box">
                <p>
                    You can register for a free account on
                    <router-link to="/register"><i class="fa fa-user"></i>register</router-link>
                </p>
                <p>
                    If you already have an account, you can
                    <router-link to="/login"><i class="fa fa-sign-in"></i>login</router-link>
                </p>
            </div>

        </div>
    </div>
</template>

<script>
export default {
    name: 'Welcome',
}
</script>

<style scoped>
.welcome {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    max-width: 720px;
    text-align: center;
    margin-top: 18rem;

}

.title {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 1rem;
}

.cloud-description {
    text-align: center;
    padding: 15px;
    font-size: 16px;
    font-weight: 500;
}

.highlight {
    color: #ffc107;
}

.router-box {
    display: flex;
    flex-direction: column;
    margin-top: 1rem;

}

.router-box p {
    width: 100%;
    margin-top: 8px;
    padding: 4px;
}


</style>