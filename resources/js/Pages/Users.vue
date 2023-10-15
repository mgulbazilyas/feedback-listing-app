<script setup lang="ts">
import Modal from "@/Components/Modal.vue";
import Layout from "@/Layouts/Layout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, onBeforeUnmount, reactive } from "vue";
import axios from "axios";
function isAnyFilterEnabled(search) {
  return search.showOnlyOwner || search.searchTerm || search.category;
}
window.appChannel.bind("voted", function (data) {
  // debugger;
  let item = pageData.items.find((item) => item.id == data.user.id);
  item.upvotes += data.upvotes;
  item.downvotes += data.downvotes;
});
window.appChannel.bind("userCreateOrUpdate", function (data) {
  // debugger;
  console.log(data);
  if (data.created) {
    if (isAnyFilterEnabled(pageData.search)) {
      pageData.items.splice(0, 0, data.user);
    }
  } else {
    let item = pageData.items.find((item) => item.id == data.user.id);
    item.title = data.title;
    item.description += data.description;
  }
});
const apiMeta = ref({});
const loading = ref(false);
const pageData = reactive({
  user: null,
  modal: {
    show: false,
    user: null,
    userIndex: null,
  },
  items: [],
  pageNo: 1,
  loading: false,
  search: {
    showOnlyOwner: false,
    searchTerm: "",
    category: "",
  },
});
const search = () => {
  pageData.pageNo = 1;
  pageData.items = [];

  setTimeout(() => {
    getPageData();
  }, 200);
};
const getPageData = () => {
  if(!loading.value){
    return new Promise((resolve, reject) => {
    loading.value = true;
    /*
    showOnlyOwner: false,
    searchTerm: "",
    category: "", */
    const queryParams = new URLSearchParams({ ...pageData.search, page: pageData.pageNo }).toString();
    fetch(`/api/users?${queryParams}`).then((response) =>
      response.json().then((response) => {
        console.log(response);
        const data = response;
        pageData.items = pageData.items.concat(data.data);
        delete data.items;
        apiMeta.value = data;
        loading.value = false;
        resolve(true);
      })
    );
  });
  }

};
const infiniteScrollTrigger = ref();
// Add scroll event listener
const handleScroll = async () => {
  const trigger = infiniteScrollTrigger.value;
  if (trigger) {
    // debugger;
    const rect = trigger.getBoundingClientRect();

    const isInView = rect.top >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight);

    if (isInView) {
      await loadMoreItems();
    } else {
      console.log("not in view");
    }
  } else {
    console.log("trigger not visible");
  }
};

onMounted(() => {
  window.addEventListener("scroll", handleScroll);
  getPageData().then(() => {
    handleScroll();
  });
});

onBeforeUnmount(() => {
  window.removeEventListener("scroll", handleScroll);
});

const loadMoreItems = () => {
  // Call your API or method to load more items
  // Update items.value with the new data
  if (apiMeta.value.next_page_url) {
    pageData.pageNo += 1;
    return getPageData();
  } else {
  }
};
const statusToText = {
  0: "Pending",
  1: "In Progress",
  2: "Completed",
};
const getStatusColorClass = (status) => {
  switch (status) {
    case 0:
      return "text-red-500"; // Tailwind CSS class for red text
    case 1:
      return "text-blue-500"; // Tailwind CSS class for blue text
    case 2:
      return "text-green-500"; // Tailwind CSS class for green text
    default:
      return "text-black"; // Default color if status is not recognized
  }
};
const createNewUser = (item) => {
  pageData.modal.user = {};
  pageData.modal.show = true;
};
const editUser = (item) => {
  pageData.modal.user = { ...item };
  pageData.modal.show = true;
};
const deleteUser = (item) => {
  const isConfirm = confirm("Are you sure you want to delete");
  if (isConfirm) {
    let config = {
      method: "delete",
      maxBodyLength: Infinity,
      url: `/api/users/${item.id}`,
    };
    pageData.loading = true;
    axios
      .request(config)
      .then((response) => {
        console.log(JSON.stringify(response.data));
        const index = pageData.items.findIndex((itm) => itm.id === item.id);
        pageData.items.splice(index, 1);
        alert("User Deleted");
      })
      .catch((error) => {
        console.log(error);
      })
      .finally(() => {
        pageData.loading = false;
      });
  } else {
  }
};
const handleSubmit = () => {
  let user = pageData.modal.user;

  if (user.id) {
    // Means this is update request
    const id = user.id;
    delete user.id;
    const raw = JSON.stringify({ ...user });
    user.id = id;
    const config = {
      method: "put",
      url: `/api/users/${id}`,
      data: raw,
      headers: { "Content-Type": "application/json" },
    };
    pageData.loading = true;
    axios(config)
      .then(function (response) {
        const index = pageData.items.findIndex((item) => item.id === id);
        pageData.items[index] = { ...user };
      })
      .catch(function (error) {
        console.log(error);
      })
      .finally(() => {
        pageData.loading = false;
        alert("user editted successfully");
        pageData.modal.show = false;
      });
  } else {
    // Create New Item
    const raw = JSON.stringify({ ...user });
    const config = {
      method: "post",
      url: `/api/users`,
      data: raw,
      headers: { "Content-Type": "application/json" },
    };
    pageData.loading = true;
    axios(config)
      .then(function (response) {
        pageData.items = [];
        pageData.pageNo = 1;
        getPageData();
      })
      .catch(function (error) {
        console.log(error);
      })
      .finally(() => {
        pageData.loading = false;
        alert("User Created successfully");
        pageData.modal.show = false;
      });
  }
};
import { usePage } from "@inertiajs/vue3";
const page = usePage();
console.log(page.props);

function vote(type = "upvote", user) {
  pageData.loading = true;
  fetch(`/api/users/${user.id}/${type}`, {
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
  })
    .then((response) =>
      response.json().then((res) => {
        if (response.status == 200) {
          user.upvotes = res.user.upvotes;
          user.downvotes = res.user.downvotes;
          user.vote_type = +(type == "upvote");
        } else {
          alert(res.message);
        }
      })
    )
    .catch((err) => console.log(err))
    .finally(() => {
      pageData.loading = false;
    });
}
</script>

<style scoped>
input {
  border: 1px solid #ccc;
  border-radius: 0;
}

button {
  border-radius: 0;
}

.infinite-scroll-trigger {
  height: 50px;
  /* Adjust this height as needed */
  background-color: #f5f5f5;
  /* Adjust the color as needed */
}

/* Add this CSS in your styles file or in your HTML file if you're not using an external stylesheet */
@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

.loader {
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid #000;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  animation: spin 1s linear infinite;
}
#userDetail {
  width: 100%;
}
.user-item {
  height: 100%;
}
</style>

<template>
  <Layout>
    <div class="row p-8 w-full">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-end">
          <input @keydown.enter="search" v-model="pageData.search.searchTerm" type="text" class="border rounded-l py-2 px-3 focus:outline-none focus:ring focus:border-blue-300" placeholder="Search..." />
          <button  @click="search" class="bg-blue-500 text-white py-2 px-4 rounded-r mr-2">Search</button>
        </div>

        <button @click="createNewUser" class="bg-green-500 text-white py-2 px-4 rounded">New User</button>
      </div>
      <h2 class="mb-2">Total Users: {{ apiMeta.total }}</h2>

      <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- User Card 1 -->
        <div class="bg-white shadow p-4 rounded mb-4 hover:shadow-xl" v-for="user in pageData.items">
          <div class="flex user-item justify-between content-between">
            <div id="userDetail" class="flex flex-col justify-between">
              <div class="mb-2">
                <h2 class="text-xl font-bold text-gray-800">{{ user.name }}</h2>
                <p class="text-sm text-gray-600 text-right">{{ user.email }}</p>
                <p class="text-sm text-gray-600">
                  Role: <b>{{ user.role }}</b>
                </p>
                <p class="text-sm text-gray-600">
                  Feedbacks: <b>{{ user.feedbacks_count }}</b>
                </p>
                <p class="text-sm text-gray-600">
                  Comments: <b>{{ user.comments_count }}</b>
                </p>
                <p class="text-sm text-gray-600">
                  Votes: <b>{{ user.votes_count }}</b>
                </p>
              </div>
              <div class="flex flex-col">
                <div class="flex items-center justify-between">
                  <p class="text-sm font-semibold">{{ user.category }}</p>
                  <p class="text-xs text-gray-400">{{ user.updated_at }}</p>
                </div>
                <div class="flex items-center justify-end mt-2">
                  <button class="bg-blue-500 text-white px-4 py-2 rounded-b mr-2" :disabled="pageData.loading" @click="editUser(user)">Edit</button>
                  <button class="bg-red-500 text-white px-4 py-2 rounded-b mr-2" :disabled="pageData.loading" @click="deleteUser(user)">Delete</button>
                  <Link class="bg-green-500 text-white px-4 py-2 rounded-b mr-2" :href="`/user/${user.id}`"> Open</Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Loading Row -->
      <div class="flex justify-center items-center mt-8 mb-8">
        <div class="loader" v-if="loading"></div>
        <h2 v-else-if="apiMeta.current_page && !apiMeta.next_page_url">No More Items</h2>
        <div class="infinite-scroll-trigger" ref="infiniteScrollTrigger" v-else-if="!loading"></div>
      </div>
    </div>
  </Layout>
  <Modal :show="pageData.modal.show">
    <div class="row">
      <div class="flex justify-between items-center mb-4 mt-2 mr-2">
        <h1 class="pl-2" v-if="pageData.modal.user.id">Update User</h1>
        <h1 class="pl-2" v-else>Create User</h1>
        <span @click="pageData.modal.show = false" class="cursor-pointer"> X </span>
      </div>
    </div>
    <div class="row p-4">
      <form @submit.prevent="handleSubmit">
        <div class="mb-4">
          <label for="title" class="block text-gray-700 font-bold mb-2">Name:</label>
          <input v-model="pageData.modal.user.name" type="text" id="title" name="title" class="border rounded w-full py-2 px-3" required />
        </div>
        <div class="mb-4">
          <label for="description" class="block text-gray-700 font-bold mb-2">Email:</label>
          <input type="email" v-model="pageData.modal.user.email" id="description" name="email" class="border rounded w-full py-2 px-3" required/>
        </div>
        <div class="mb-4">
          <label for="status" class="block text-gray-700 font-bold mb-2">Role:</label>
          <div class="flex items-center">
            <input value="user" v-model="pageData.modal.user.role" type="radio" id="user" name="radioGroup" class="form-radio h-4 w-4 text-indigo-600 transition duration-150 ease-in-out" checked />
            <label for="user" class="ml-2 block text-sm leading-5 text-gray-900"> User </label>
          </div>

          <div class="flex items-center mt-2">
            <input value="admin" v-model="pageData.modal.user.role" type="radio" id="admin" name="radioGroup" class="form-radio h-4 w-4 text-indigo-600 transition duration-150 ease-in-out" />
            <label for="admin" class="ml-2 block text-sm leading-5 text-gray-900"> Admin </label>
          </div>
        </div>
        <div class="mb-4">
          <label for="description" class="block text-gray-700 font-bold mb-2"><span v-if="pageData.modal.user.id">Change</span> Password:</label>
          <input type="password" v-model="pageData.modal.user.password" id="description" name="email" class="border rounded w-full py-2 px-3"/>
        </div>
        <div class="flex justify-center items-center mt-8 mb-8" v-if="pageData.loading">
          <div class="loader"></div>
        </div>
        <div class="flex justify-end" v-else>
          <button @click="handleSubmit" class="bg-blue-500 text-white px-4 py-2 rounded-full">Save</button>
        </div>
      </form>
    </div>
  </Modal>
</template>
