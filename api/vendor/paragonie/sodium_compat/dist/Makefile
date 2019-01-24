# This builds sodium-compat.phar. To run this Makefile, `box` and `composer`
# must be installed and in your $PATH. Run it from inside the dist/ directory.

box := $(shell which box)
composer := "composer"
gitcommit := $(shell git rev-parse HEAD)

.PHONY: all
all: build-phar

.PHONY: sign-phar
sign-phar:
	gpg -u 7F52D5C61D1255C731362E826B97A1C2826404DA --armor --output sodium-compat.phar.sig --detach-sig sodium-compat.phar

# ensure we run in clean tree. export git tree and run there.
.PHONY: build-phar
build-phar:
	@echo "Creating .phar from revision $(shell git rev-parse HEAD)."
	rm -rf worktree
	install -d worktree
	(cd $(CURDIR)/..; git archive HEAD) | tar -x -C worktree
	$(MAKE) -f $(CURDIR)/Makefile -C worktree sodium-compat.phar
	mv worktree/*.phar .
	rm -rf worktree

.PHONY: clean
clean:
	rm -vf sodium-compat.phar sodium-compat.phar.sig

# Inside workdir/:

sodium-compat.phar: dist/box.json composer.lock
	cp dist/box.json .
	php -d phar.readonly=0 $(box) build -c box.json -v

composer.lock:
	$(composer) config autoloader-suffix $(gitcommit)
	$(composer) install --no-dev

