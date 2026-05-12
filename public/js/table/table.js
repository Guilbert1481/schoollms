function sortTable(column) {
    let url = new URL(window.location.href);
    let currentSort = url.searchParams.get("sort");
    let currentDirection = url.searchParams.get("direction");

    let direction = "asc";

    if (currentSort === column && currentDirection === "asc") {
        direction = "desc";
    }

    url.searchParams.set("sort", column);
    url.searchParams.set("direction", direction);

    window.location.href = url.toString();
}